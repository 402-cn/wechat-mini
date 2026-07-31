var __mpSiteRoot = '';
var __mpAssetRoot = '';
try {
  var __cfg = require('../../../utils/mp_config.js');
  var r = __cfg.siteRoot || '';
  var ar = __cfg.assetRoot || __cfg.siteRoot || '';
  if (!r && __cfg.apiBase) {
    var a = __cfg.apiBase;
    r = a.endsWith('/api') ? a.slice(0, -4) : a.replace(/\/api\/?$/, '');
  }
  if (!ar) ar = r;
  if (r) __mpSiteRoot = r;
  if (ar) __mpAssetRoot = ar;
} catch (e) {}

Page({
  data: { appModalShow: false, appModalTitle: '提示', appModalContent: '',  siteRoot: __mpSiteRoot, assetRoot: __mpAssetRoot, showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#607d8b", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}], orderLoggedIn: false, orderList: [], orderStatus: '' },
  onLoad(q) {
    if (__mpSiteRoot && __mpSiteRoot !== this.data.siteRoot) this.setData({ siteRoot: __mpSiteRoot });
    if (__mpAssetRoot && __mpAssetRoot !== this.data.assetRoot) this.setData({ assetRoot: __mpAssetRoot });
    if (q && q.component_id) {
      this._queryCid = q.component_id;
      if (this.data.productFullCid !== undefined) {
        this.setData({ productFullCid: q.component_id });
      }
    }
    if (this.onLoadProductFull) this.onLoadProductFull(q);
    if (this.onLoadArticleFull) this.onLoadArticleFull(q);
    if (this.onLoadOrderStatus) this.onLoadOrderStatus(q);
    if (this.resolveGridNavPromoImages) this.resolveGridNavPromoImages();
    if (this.seedDemoImages) this.seedDemoImages();
  },
  submitForm(e) {
    const formId = e.currentTarget.dataset.formId;
    wx.showToast({ title: '请对接 api/form/' + formId + '/submit', icon: 'none' });
  },
onLoadOrderStatus(q) {
    let status = (q && q.status) || '';
    try {
      const app = getApp();
      if (app && app.globalData && app.globalData.orderListStatus) {
        status = app.globalData.orderListStatus;
        app.globalData.orderListStatus = '';
      }
    } catch (e) {}
    this.setData({ orderStatus: status || '' });
  },
  async loadOrderPage() {
    const { req, assetUrl } = require('../../../utils/api');
    const status = this.data.orderStatus || '';
    let url = '/order/list.php?page=1';
    if (status) url += '&status=' + encodeURIComponent(status);
    try {
      const j = await req(url);
      if (j.code !== 0 || !j.data || j.data.logged_in === false) {
        this.setData({ orderLoggedIn: false, orderList: [] });
        return;
      }
      const list = (j.data.list || []).map(function(o) {
        const copy = Object.assign({}, o);
        copy.items = (copy.items || []).map(function(it) {
          return Object.assign({}, it, { product_image: assetUrl(it.product_image) });
        });
        return copy;
      });
      this.setData({ orderLoggedIn: true, orderList: list });
    } catch (e) {
      this.setData({ orderLoggedIn: false, orderList: [] });
    }
  },
  switchOrderTab(e) {
    const status = e.currentTarget.dataset.status || '';
    if (status === this.data.orderStatus) return;
    this.setData({ orderStatus: status });
    this.loadOrderPage();
  },
  async doOrderLogin() {
    const { wxLoginWithProfile, toastMsg } = require('../../../utils/api');
    const j = await wxLoginWithProfile();
    wx.showToast({ title: toastMsg(j, '登录成功', '登录失败'), icon: 'none' });
    if (j.code === 0) this.loadOrderPage();
  },
  goOrderDetail(e) {
    wx.navigateTo({ url: '/packageSys/pages/order-detail/order-detail?id=' + e.currentTarget.dataset.id });
  },
  goOrderPay(e) {
    wx.navigateTo({ url: '/packageSys/pages/checkout/checkout?order_id=' + e.currentTarget.dataset.id + '&from_cart=0' });
  },
  async cancelOrder(e) {
    const { req, toastMsg } = require('../../../utils/api');
    const j = await req('/order/cancel.php', 'POST', { order_id: e.currentTarget.dataset.id });
    wx.showToast({ title: toastMsg(j, '已取消', '取消失败'), icon: 'none' });
    if (j.code === 0) this.loadOrderPage();
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
onShow() {
    if (this.loadOrderPage) this.loadOrderPage().catch(function(){});
    if (this.bootstrapWidgetImages) this.bootstrapWidgetImages();
  },
  onReady() {
    // onShow 已负责加载，避免重复触发导致 DevTools 竞态
  },
noop() {},
  closeAppModal() {
    this.setData({ appModalShow: false, appModalTitle: '提示', appModalContent: '' });
    if (this._appModalResolve) {
      const fn = this._appModalResolve;
      this._appModalResolve = null;
      fn();
    }
  }
})