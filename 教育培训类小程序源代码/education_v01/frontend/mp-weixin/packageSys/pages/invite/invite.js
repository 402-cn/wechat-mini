const { req, wxLoginWithProfile, toastMsg } = require('../../../utils/api');
Page({
  data: { showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#2980b9", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}],  appModalShow: false, appModalTitle: '提示', appModalContent: '',  code: '', count: 0, points: 0, loggedIn: false },
  onShow() { this.load(); },
  async load() {
    const j = await req('/invite/info.php');
    if (j.code !== 0 || !j.data || j.data.logged_in === false) { this.setData({ loggedIn: false, code: '', count: 0, points: 0 }); return; }
    this.setData({ loggedIn: true, code: j.data.invite_code || '', count: j.data.invite_count || 0, points: j.data.invite_points || 0 });
  },
  async doLogin() {
    const j = await wxLoginWithProfile();
    if (j.code === 0) { wx.showToast({ title: toastMsg(j, '登录成功', '登录失败'), icon: 'none' }); this.load(); }
    else wx.showToast({ title: toastMsg(j, '登录成功', '登录失败'), icon: 'none' });
  },
  copy() {
    wx.setClipboardData({ data: this.data.code, success: () => wx.showToast({ title: '已复制', icon: 'none' }) });
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