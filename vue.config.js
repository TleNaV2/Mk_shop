const { defineConfig } = require('@vue/cli-service')
const path = require('path')

const projectPath = `/${path.basename(__dirname)}/php_api`

module.exports = defineConfig({
  transpileDependencies: true,
  publicPath: process.env.NODE_ENV === 'production'
    ? `/${path.basename(__dirname)}/dist/`
    : '/',
  devServer: {
    proxy: {
      '/php_api': {
        target: 'http://localhost',
        changeOrigin: true,
        pathRewrite: {
          '^/php_api': projectPath
        }
      }
    }
  }
})
