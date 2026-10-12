import json
import os
from pathlib import Path
import subprocess
import tempfile
import textwrap

WORKTREE = Path(__file__).resolve().parents[2]

def command(args, *, cwd=None, env=None, check=True):
    return subprocess.run(args, cwd=cwd, env=env, text=True,
                          stdout=subprocess.PIPE, stderr=subprocess.PIPE, check=check)

def workflow_script(path, job):
    # Execute the real inline shell body, rather than a copy of its implementation.
    section = path.read_text().split(f'\n  {job}:\n', 1)[1]
    return textwrap.dedent(section.split('\n        run: |\n', 1)[1])

publish = workflow_script(WORKTREE / '.github/workflows/wiki.yml', 'publish')
cleanup = workflow_script(WORKTREE / '.github/workflows/wiki-maintenance.yml', 'cleanup')

with tempfile.TemporaryDirectory(prefix='container-wiki-check-') as directory:
    root = Path(directory)
    remote = root / 'wiki.git'
    seed = root / 'seed'
    binary = root / 'bin'
    binary.mkdir()
    stub = binary / 'gh'
    stub.write_text('''#!/usr/bin/env python3
import os,pathlib,sys
if os.environ.get('FAKE_API_FAIL') == '1':
    print('Fixture API failure', file=sys.stderr)
    sys.exit(1)
counter=pathlib.Path(os.environ['FAKE_COUNTER'])
index=int(counter.read_text()) if counter.exists() else 0
values=os.environ['FAKE_VALUES'].split(',')
print(values[min(index,len(values)-1)])
counter.write_text(str(index+1))
''')
    stub.chmod(0o755)
    env = dict(os.environ, PATH=str(binary) + os.pathsep + os.environ['PATH'],
               GIT_CONFIG_GLOBAL=str(root / 'empty-git-config'), GIT_CONFIG_NOSYSTEM='1',
               GITHUB_REPOSITORY='fixture/container', GH_TOKEN='fixture-not-a-credential',
               SOURCE_SHA='a' * 40, EVENT_NAME='pull_request', PR_NUMBER='15', DEFAULT_BRANCH='main')
    command(['git', 'init', '--bare', '--initial-branch=master', str(remote)], env=env)
    command(['git', 'clone', str(remote), str(seed)], env=env)
    command(['git', 'config', 'user.name', 'Fixture'], cwd=seed, env=env)
    command(['git', 'config', 'user.email', 'fixture@example.invalid'], cwd=seed, env=env)
    (seed / 'Home.md').write_text('Original API\n')
    (seed / 'Manual.md').write_text('Independently maintained page\n')
    command(['git', 'add', '.'], cwd=seed, env=env)
    command(['git', 'commit', '-m', 'Fixture baseline'], cwd=seed, env=env)
    command(['git', 'push', 'origin', 'master'], cwd=seed, env=env)
    address = 'https://github.com/$GITHUB_REPOSITORY.wiki.git'
    publisher = publish.replace(address, str(remote))
    cleaner = cleanup.replace(address, str(remote))

    def ref(branch):
        result = command(['git', '--git-dir=' + str(remote), 'rev-parse', '--verify',
                          'refs/heads/' + branch], env=env, check=False)
        return result.stdout.strip() if result.returncode == 0 else None

    def blob(branch, page):
        return command(['git', '--git-dir=' + str(remote), 'show', branch + ':' + page], env=env).stdout

    results = []
    def execute(name, script, values, *, fail=False, event='pull_request', agent=False):
        folder = root / name
        folder.mkdir()
        pages = folder / 'wiki-pages'
        pages.mkdir()
        (pages / 'Home.md').write_text(name + '\n')
        if agent:
            (pages / 'AGENTS.md').write_text('Must never be published\n')
        test_env = dict(env, EVENT_NAME=event, FAKE_VALUES=','.join(values),
                        FAKE_COUNTER=str(folder / 'counter'), FAKE_API_FAIL='1' if fail else '0')
        result = command(['bash', '-c', script], cwd=folder, env=test_env, check=False)
        results.append({'case': name, 'exit': result.returncode})
        return result

    original = ref('master')
    assert execute('preview', publisher, ['a' * 40]).returncode == 0
    assert ref('pr-15') and ref('master') == original
    assert blob('pr-15', 'Home.md') == 'preview\n'
    assert blob('pr-15', 'Manual.md') == 'Independently maintained page\n'
    preview_ref = ref('pr-15')
    assert execute('stale', publisher, ['b' * 40]).returncode == 0
    assert ref('pr-15') == preview_ref
    assert execute('closed', publisher, ['']).returncode == 0
    assert ref('pr-15') == preview_ref
    assert execute('closed-during-build', publisher, ['a' * 40, '']).returncode == 0
    assert ref('pr-15') == preview_ref
    assert execute('api-error', publisher, [''], fail=True).returncode != 0
    assert ref('pr-15') == preview_ref
    assert execute('instruction-file', publisher, ['a' * 40], agent=True).returncode != 0
    assert ref('pr-15') == preview_ref
    assert execute('merged-tree', publisher, ['a' * 40], event='push').returncode == 0
    assert ref('master') != original
    assert blob('master', 'Home.md') == 'merged-tree\n'
    assert blob('master', 'Manual.md') == 'Independently maintained page\n'
    assert execute('reopened-cleanup', cleaner, ['open']).returncode == 0
    assert ref('pr-15') == preview_ref
    assert execute('reopened-during-cleanup', cleaner, ['closed', 'open']).returncode == 0
    assert ref('pr-15') == preview_ref
    master_ref = ref('master')
    assert execute('closed-cleanup', cleaner, ['closed']).returncode == 0
    assert ref('pr-15') is None and ref('master') == master_ref
    assert execute('missing-preview-cleanup', cleaner, ['closed']).returncode == 0
    assert ref('master') == master_ref
    print(json.dumps({'result': 'passed', 'cases': results, 'live_network': False}))
