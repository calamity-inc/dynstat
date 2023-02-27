# dynstat

Develop dynamically, deploy statically.

## Deploy to GitHub Pages

1. Add the [dynstat.yml](https://github.com/calamity-inc/dynstat-demo/blob/senpai/.github/workflows/dynstat.yml) to your .github/workflows folder.
2. Set GitHub Pages "Source" to "Deploy from a branch", select the "gh-pages" branch, and click "Save."
3. That's it!

## Config

You can configure dynstat with by creating `.dynstat.json` file.

### Per-build

- `minify` (default: `false`)
- `skip_empty` (default: `false`) — don't write empty files to build directory?
- `nojekyll` (default: `true`) — create .nojekyll file in build directory?

### Global

- `php_ext` (default: `[".php"]`)
- `builds` (default: `{"build":{}}`)

### Builds

Dynstat can create multiple builds, each with their own config. For example, this creates the regular "build" plus a minified version:

```JSON
{
    "minify": false,
    "builds": {
        "build": {},
        "minified": {
            "minify": true
        }
    }
}
```

## Runtime

You can detect that your script is being executed by dynstat to produce a static build by using `!empty($_DYNSTAT)`.

`$_DYNSTAT` is an array, which provides the following keys:

- `BUILD_NAME` (string)

## Install

To have access to the `dynstat` command anywhere, you can install dynstat with Chocolatey, simply run the following command in Command Prompt (Admin):

```batch
powershell -command "(New-Object Net.WebClient).DownloadFile('https://calamity.gg/chocolatey/dynstat.nupkg', 'dynstat.nupkg')" && choco install dynstat -s . -y && del dynstat.nupkg
```

## Credits

The minify option is powered by [Mecha CMS' Minify Engine](https://github.com/mecha-cms/x.minify).
