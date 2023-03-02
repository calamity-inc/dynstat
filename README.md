# dynstat

Develop dynamically, deploy statically.

## Deploy to GitHub Pages

1. Add the [dynstat.yml](https://github.com/calamity-inc/dynstat-demo/blob/senpai/.github/workflows/dynstat.yml) to your .github/workflows folder.
2. Set GitHub Pages "Source" to "Deploy from a branch", select the "gh-pages" branch, and click "Save."
3. That's it!

## Config

You can configure dynstat with by creating `.dynstat.json` file. Any keys not provided are implied to be the default value:

```JSON
{
    "minify": false,
    "skip_empty": false,
    "php_ext": [".php"]
}
```

## Runtime

You can detect that your script is being executed by dynstat to produce a static build by using `!empty($_DYNSTAT)`.

## Credits

The minify option is powered by [Mecha CMS' Minify Engine](https://github.com/mecha-cms/x.minify).
