const { req, wxLoginWithProfile, toastMsg } = require('../../../utils/api');
Page({
  data: { showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#16a085", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}],  appModalShow: false, appModalTitle: '提示', appModalContent: '',  list: [], name: '', phone: '', detail: '', isDefault: false, loggedIn: false },
  onShow() { this.load(); },
  async load() {
    const j = await req('/address/list.php');
    if (j.code !== 0 || !j.data || j.data.logged_in === false) { this.setData({ loggedIn: false, list: [] }); return; }
    this.setData({ loggedIn: true, list: j.data.list || [] });
  },
  async doLogin() {
    const j = await wxLoginWithProfile();
    if (j.code === 0) { wx.showToast({ title: toastMsg(j, '登录成功', '登录失败'), icon: 'none' }); this.load(); }
    else wx.showToast({ title: toastMsg(j, '登录成功', '登录失败'), icon: 'none' });
  },
  onName(e){ this.setData({ name: e.detail.value }); },
  onPhone(e){ this.setData({ phone: e.detail.value }); },
  onDetail(e){ this.setData({ detail: e.detail.value }); },
  toggleDefault(){ this.setData({ isDefault: !this.data.isDefault }); },
  async save() {
    const j = await req('/address/save.php', 'POST', { name: this.data.name, phone: this.data.phone, detail: this.data.detail, is_default: this.data.isDefault ? 1 : 0 });
    wx.showToast({ title: toastMsg(j, '保存成功', '保存失败'), icon: 'none' });
    if (j.code === 0) { this.setData({ name:'', phone:'', detail:'', isDefault:false }); this.load(); }
  },
  async remove(e) {
    const j = await req('/address/delete.php', 'POST', { id: e.currentTarget.dataset.id });
    if (j.code === 0) { wx.showToast({ title: toastMsg(j, '删除成功', '删除失败'), icon: 'none' }); this.load(); }
    else wx.showToast({ title: toastMsg(j, '删除成功', '删除失败'), icon: 'none' });
  },
  async setDefault(e) {
    const j = await req('/address/set_default.php', 'POST', { id: e.currentTarget.dataset.id });
    if (j.code === 0) { wx.showToast({ title: toastMsg(j, '已设为默认', '操作失败'), icon: 'none' }); this.load(); }
    else wx.showToast({ title: toastMsg(j, '已设为默认', '操作失败'), icon: 'none' });
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