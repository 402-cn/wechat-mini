const mpConfig = require('./utils/mp_config.js');
const { wxLoginWithProfile } = require('./utils/api.js');
function mpSiteRoot(api) {
  var a = api || '';
  if (a.endsWith('/api')) return a.slice(0, -4);
  return a.replace(/\/api\/?$/, '');
}
App({
  globalData: { apiBase: mpConfig.apiBase || '', siteRoot: mpConfig.siteRoot || '', assetRoot: mpConfig.assetRoot || mpConfig.siteRoot || '' },
  onLaunch() {
    if (mpConfig.apiBase) this.globalData.apiBase = mpConfig.apiBase;
    if (!this.globalData.apiBase) {
      try {
        if (wx.getAccountInfoSync().miniProgram.envVersion === 'develop') {
          console.warn('[mp] 请在 utils/mp_config.js 配置 apiBase（与 PHP 站点 /api 一致）');
        }
      } catch (e) {}
    }
    if (!this.globalData.siteRoot) {
      this.globalData.siteRoot = mpSiteRoot(this.globalData.apiBase);
    }
    if (!this.globalData.assetRoot) {
      this.globalData.assetRoot = mpConfig.assetRoot || this.globalData.siteRoot || '';
    }
    wxLoginWithProfile().then(function() {
      try {
        var pages = getCurrentPages();
        var page = pages[pages.length - 1];
        if (page && typeof page.loadCenter === 'function') page.loadCenter();
      } catch (e) {}
    }).catch(function() {});
  }
})