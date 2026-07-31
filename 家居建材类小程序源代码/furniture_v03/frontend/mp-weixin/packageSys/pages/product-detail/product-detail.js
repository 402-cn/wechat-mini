const { req, assetUrl } = require('../../../utils/api');
function mpDecodeHtml(s) {
  return String(s || '').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#39;/g, "'");
}
Page({
  data: { showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#e74c3c", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}],  appModalShow: false, appModalTitle: '提示', appModalContent: '',  product: null },
  onLoad(q) { this.pid = q.id; this.load(); },
  async load() {
    const j = await req('/product/detail.php?id=' + encodeURIComponent(this.pid));
    if (j.code === 0 && j.data) {
      const desc = mpDecodeHtml(j.data.description || '');
      const p = Object.assign({}, j.data, { image: assetUrl(j.data.image), descriptionHtml: desc });
      this.setData({ product: p });
    }
  },
  goBack() {
    const pages = getCurrentPages();
    if (pages.length > 1) wx.navigateBack();
    else wx.switchTab({ url: '/pages/home/home', fail() { wx.reLaunch({ url: '/pages/home/home' }); } });
  },
  async addCart() {
    const { showAppModal } = require('../../../utils/api');
    const j = await req('/cart/add.php', 'POST', { product_id: this.pid, quantity: 1 });
    if (j.code === 401) {
      wx.showModal({ title: '提示', content: j.message || '请先登录', confirmText: '知道了', showCancel: false });
      return;
    }
    await showAppModal(j.message || (j.code === 0 ? '加入购物车成功' : '加入购物车失败'));
  },
  async buyNow() {
    const j = await req('/order/create.php', 'POST', { from_cart: 0, product_id: this.pid, quantity: 1 });
    if (j.code !== 0) return wx.showToast({ title: j.message || '失败', icon: 'none' });
    wx.navigateTo({ url: '/packageSys/pages/checkout/checkout?order_id=' + j.data.order_id + '&from_cart=0' });
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