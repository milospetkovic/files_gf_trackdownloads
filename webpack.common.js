const path = require('path')
const BundleAnalyzerPlugin = require('@bundle-analyzer/webpack-plugin')

const config = {
	entry: {
		main:path.join(__dirname, 'src', 'main.js'),
		unconfirmed_files:path.join(__dirname, 'src', 'unconfirmed_files.js')
	},
	output: {
		path: path.resolve(__dirname, './js'),
		publicPath: '/js/',
		filename: './build/[name].js',
		chunkFilename: 'chunks/[name]-[hash].js',
	},
	module: {
		rules: [
			{
				test: /\.css$/,
				use: ['vue-style-loader', 'css-loader'],
			},
			{
				test: /\.scss$/,
				use: ['vue-style-loader', 'css-loader', 'sass-loader'],
			},
		],
	},
	resolve: {
		extensions: ['*', '.js', '.vue'],
		symlinks: false,
	},
}

if (process.env.BUNDLE_ANALYZER_TOKEN) {
	config.plugins.push(new BundleAnalyzerPlugin({ token: process.env.BUNDLE_ANALYZER_TOKEN }))
}

module.exports = config
