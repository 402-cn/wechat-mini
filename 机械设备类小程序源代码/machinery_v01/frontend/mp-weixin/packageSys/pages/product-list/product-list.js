const { req, assetUrl, toastMsg } = require('../../../utils/api');
Page({
  data: { showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#607d8b", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}],  appModalShow: false, appModalTitle: '提示', appModalContent: '',  productFullCid: '', productFullLayout: 'grid', productFullCols: 2, productCatLayout: 'top', productShowAddCart: true, productCategoryId: 0, productCategories: [], productListItems: [], productListPage: 1, productListHasMore: true, productListLoading: false },
  onLoad(q) {
    if (q && q.title) wx.setNavigationBarTitle({ title: decodeURIComponent(q.title) });
    if (q && q.component_id) this._queryCid = q.component_id;
  },
  onShow() { this.initProductFullPage(); },
  onReachBottom() {
    if (this.loadProductFullPageMore) this.loadProductFullPageMore();
  },
  async loadProductCategories() {
    const { req } = require('../../../utils/api');
    try {
      const j = await req('/product/categories.php');
      if (j.code === 0) {
        this.setData({ productCategories: j.data.list || [] });
      }
    } catch (e) {}
  },
  async loadProductFullPage(reset) {
    const { req, assetUrl } = require('../../../utils/api');
    if (this.data.productListLoading) return;
    let page = reset ? 1 : (this.data.productListPage || 1);
    if (!reset && !this.data.productListHasMore) return;
    this.setData({ productListLoading: true });
    let url = '/product/list.php?page=' + page + '&page_size=20';
    if (this.data.productCategoryId > 0) url += '&category_id=' + this.data.productCategoryId;
    try {
      const j = await req(url);
      if (j.code !== 0) {
        this.setData({ productListLoading: false });
        return;
      }
      let list = (j.data.list || []).map(function(p) {
        return Object.assign({}, p, { imageSrc: assetUrl(p.image || ''), image: assetUrl(p.image || '') });
      });
      const merged = reset ? list : (this.data.productListItems || []).concat(list);
      const hasMore = list.length >= 20;
      this.setData({
        productListItems: merged,
        productListPage: page + 1,
        productListHasMore: hasMore,
        productListLoading: false,
      });
    } catch (e) {
      this.setData({ productListLoading: false });
    }
  },
  loadProductFullPageMore() {
    if (this.data.productListLoading || !this.data.productListHasMore) return;
    this.loadProductFullPage(false);
  },
  pickProductCategory(e) {
    const id = parseInt(e.currentTarget.dataset.id, 10) || 0;
    if (id === this.data.productCategoryId) return;
    this.setData({ productCategoryId: id });
    this.loadProductFullPage(true);
  },
  async initProductFullPage() {
    await this.loadProductCategories();
    await this.loadProductFullPage(true);
  },
  onLoadProductFull(q) {
    if (q && q.component_id) {
      this._queryCid = q.component_id;
      this.setData({ productFullCid: q.component_id });
    }
  },
  goProduct(e) {
    wx.navigateTo({ url: '/packageSys/pages/product-detail/product-detail?id=' + e.currentTarget.dataset.id });
  },
  async addCartFromList(e) {
    const id = e.currentTarget.dataset.id;
    const { req, toastMsg, showAppModal } = require('../../../utils/api');
    const j = await req('/cart/add.php', 'POST', { product_id: id, quantity: 1 });
    if (j.code === 401) {
      wx.showModal({ title: '提示', content: j.message || '请先登录', confirmText: '去登录', success(res) {
        if (res.confirm) wx.navigateTo({ url: '/packageSys/pages/login/login' });
      }});
      return;
    }
    await showAppModal(toastMsg(j, '加入购物车成功', '加入购物车失败'));
    if (j.code === 0 && typeof this.loadCartPage === 'function') this.loadCartPage();
  },
  async buyNowFromList(e) {
    const id = e.currentTarget.dataset.id;
    const { req, toastMsg } = require('../../../utils/api');
    const j = await req('/order/create.php', 'POST', { from_cart: 0, product_id: id, quantity: 1, address_name: '', address_phone: '', address_detail: '' });
    if (j.code === 401) {
      wx.showModal({ title: '提示', content: j.message || '请先登录', confirmText: '去登录', success(res) {
        if (res.confirm) wx.navigateTo({ url: '/packageSys/pages/login/login' });
      }});
      return;
    }
    if (j.code !== 0 || !j.data) return wx.showToast({ title: toastMsg(j, '', '下单失败'), icon: 'none' });
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
goBack() {
    const pages = getCurrentPages();
    if (pages.length > 1) wx.navigateBack();
    else wx.switchTab({ url: '/pages/home/home', fail() { wx.reLaunch({ url: '/pages/home/home' }); } });
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