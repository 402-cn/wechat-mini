const { req, wxLoginWithProfile, toastMsg, assetUrl } = require('../../../utils/api');
Page({
  data: { showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#16a085", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}],  appModalShow: false, appModalTitle: '提示', appModalContent: '',  list: [], loggedIn: false, status: '' },
  onLoad(q) { this.setData({ status: q.status || '' }); },
  onShow() { this.load(); },
  switchTab(e) {
    const status = e.currentTarget.dataset.status || '';
    if (status === this.data.status) return;
    this.setData({ status: status });
    this.load();
  },
  async load() {
    let url = '/order/list.php?page=1';
    if (this.data.status) url += '&status=' + encodeURIComponent(this.data.status);
    const j = await req(url);
    if (j.code !== 0 || !j.data || j.data.logged_in === false) { this.setData({ loggedIn: false, list: [] }); return; }
    const list = (j.data.list || []).map(function(o) {
      const copy = Object.assign({}, o);
      copy.items = (copy.items || []).map(function(it) {
        return Object.assign({}, it, { product_image: assetUrl(it.product_image) });
      });
      return copy;
    });
    this.setData({ loggedIn: true, list: list });
  },
  async doLogin() {
    const j = await wxLoginWithProfile();
    if (j.code === 0) { wx.showToast({ title: toastMsg(j, '登录成功', '登录失败'), icon: 'none' }); this.load(); }
    else wx.showToast({ title: toastMsg(j, '登录成功', '登录失败'), icon: 'none' });
  },
  goPay(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: '/packageSys/pages/checkout/checkout?order_id=' + id + '&from_cart=0' });
  },
  goDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: '/packageSys/pages/order-detail/order-detail?id=' + id });
  },
  async cancelOrder(e) {
    const id = e.currentTarget.dataset.id;
    const j = await req('/order/cancel.php', 'POST', { order_id: id });
    wx.showToast({ title: toastMsg(j, '已取消', '取消失败'), icon: 'none' });
    if (j.code === 0) this.load();
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