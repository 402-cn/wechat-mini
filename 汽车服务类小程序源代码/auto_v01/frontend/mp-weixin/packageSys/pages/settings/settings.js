const { req, clearSession } = require('../../../utils/api');
Page({
  data: { showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#34495e", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}],  appModalShow: false, appModalTitle: '提示', appModalContent: '',  nickname: '', phone: '' },
  onShow() { this.load(); },
  async load() {
    const j = await req('/user/profile.php');
    if (j.code === 0 && j.data.logged_in) this.setData({ nickname: j.data.user.nickname || '', phone: j.data.user.phone || '' });
  },
  onNick(e){ this.setData({ nickname: e.detail.value }); },
  onPhone(e){ this.setData({ phone: e.detail.value }); },
  async save() {
    const j = await req('/user/profile.php', 'POST', { nickname: this.data.nickname, phone: this.data.phone });
    wx.showToast({ title: j.message || '已保存', icon: 'none' });
  },
  async logout() {
    await req('/auth/logout.php', 'POST', {});
    clearSession();
    wx.showToast({ title: '已退出', icon: 'none' });
    wx.navigateBack();
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