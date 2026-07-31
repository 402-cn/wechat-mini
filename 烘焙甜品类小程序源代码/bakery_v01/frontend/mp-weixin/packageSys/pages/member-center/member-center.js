const { req } = require('../../../utils/api');
Page({
  data: { showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#f39c12", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}],  appModalShow: false, appModalTitle: '提示', appModalContent: '',  levelName: '普通会员', points: 0, balance: 0, levels: [], curLevel: 0 },
  onShow() { this.load(); },
  async load() {
    const j = await req('/user/center.php');
    if (j.code !== 0) return;
    const d = j.data || {}; const u = d.user || {};
    this.setData({ levelName: u.member_level_name || '普通会员', points: u.points || 0, balance: u.balance || 0, levels: d.member_levels || [], curLevel: u.member_level || 0 });
  },
  async upgrade(e) {
    const j = await req('/user/vip_open.php', 'POST', { level_id: e.currentTarget.dataset.id });
    wx.showToast({ title: j.message || '操作完成', icon: 'none' });
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