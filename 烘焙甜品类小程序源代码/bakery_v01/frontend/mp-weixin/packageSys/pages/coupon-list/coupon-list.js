const { req } = require('../../../utils/api');
Page({
  data: { showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#f39c12", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}],  appModalShow: false, appModalTitle: '提示', appModalContent: '',  tab: 'available', list: [] },
  onShow() { this.load(); },
  switchTab(e) { this.setData({ tab: e.currentTarget.dataset.tab }); this.load(); },
  async load() {
    const url = this.data.tab === 'mine' ? '/coupon/my.php' : '/coupon/list.php';
    const j = await req(url);
    if (!j || j.code !== 0) { this.setData({ list: [] }); return; }
    if (this.data.tab === 'mine' && j.data && j.data.logged_in === false) {
      wx.showModal({ title: '提示', content: '请先登录', confirmText: '去登录', success(res) {
        if (res.confirm) wx.navigateTo({ url: '/packageSys/pages/login/login' });
      }});
      this.setData({ list: [] });
      return;
    }
    if (j.code === 0) this.setData({ list: j.data.list || [] });
  },
  async receive(e) {
    const j = await req('/coupon/receive.php', 'POST', { coupon_id: e.currentTarget.dataset.id });
    wx.showToast({ title: j.message || '领取', icon: 'none' });
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