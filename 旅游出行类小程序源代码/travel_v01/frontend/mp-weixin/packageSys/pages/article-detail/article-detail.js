const { req, assetUrl } = require('../../../utils/api');
function mpDecodeHtml(s) {
  return String(s || '').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#39;/g, "'");
}
function mpRewriteHtmlAssets(html) {
  return mpDecodeHtml(html).replace(/(<img[^>]+src=["'])([^"']+)(["'])/gi, function(_, pre, src, post) {
    return pre + assetUrl(src) + post;
  });
}
Page({
  data: { showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#00bcd4", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}],  appModalShow: false, appModalTitle: '提示', appModalContent: '',  article: null },
  onLoad(q) { this.aid = q.id; this.load(); },
  async load() {
    const j = await req('/article/detail.php?id=' + encodeURIComponent(this.aid));
    if (j.code === 0 && j.data) {
      const a = j.data;
      this.setData({
        article: Object.assign({}, a, {
          coverSrc: assetUrl(a.cover || ''),
          contentHtml: mpRewriteHtmlAssets(a.content || '')
        })
      });
    } else wx.showToast({ title: j.message || '加载失败', icon: 'none' });
  },
  goBack() {
    const pages = getCurrentPages();
    if (pages.length > 1) wx.navigateBack();
    else wx.switchTab({ url: '/pages/home/home', fail() { wx.reLaunch({ url: '/pages/home/home' }); } });
  },
noop() {},
  closeAppModal() {
    this.setData({ appModalShow: false, appModalTitle: '提示', appModalContent: '' });
    if (this._appModalResolve) {
      const fn = this._appModalResolve;
      this._appModalResolve = null;
      fn();
    }
  },
  onMpTabSwitch(e) {
    const key = e.currentTarget.dataset.key;
    if (!key) return;
    wx.switchTab({
      url: '/pages/' + key + '/' + key,
      fail: function() {
        wx.reLaunch({ url: '/pages/' + key + '/' + key });
      }
    });
  },
})