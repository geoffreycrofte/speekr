Speekr - WordPress Plugin for Speakers
====================================

We really should write something about it… Later.

## Contribution

Of course they are welcome.
To contribute the development of this plugin, clone this repository, and go to the `dev` branch.

`
	git clone https://github.com/geoffreycrofte/speekr.git
	git checkout dev
`

Then you have some commands at your disposal, but first do a small initialization.
If you don't have NPM yet, here is a tutorial for [installing it](https://nodejs.org/en/download/package-manager).

`
	npm ci
`

Once the packages are installed, you can use watch to auto generate CSS files:

`
	npm run watch
`

You also have these:

- Compile CSS: `npm run compile:css`
- Minify + CSS Map: `npm run minify:css`
- Lint the JS scripts: `npm run lint:js`
