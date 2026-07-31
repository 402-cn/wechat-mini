var __mpSiteRoot = '';
var __mpAssetRoot = '';
try {
  var __cfg = require('../../utils/mp_config.js');
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
  data: { appModalShow: false, appModalTitle: '提示', appModalContent: '',  siteRoot: __mpSiteRoot, assetRoot: __mpAssetRoot, products_jewelry_v01_cart_03: [{"id":1,"image":"/uploads/stock/jewelry_10.jpg","imageSrc":"","name":"钻石项链","price":299},{"id":2,"image":"/uploads/stock/jewelry_11.jpg","imageSrc":"","name":"珍珠项链","price":898},{"id":3,"image":"/uploads/stock/jewelry_12.jpg","imageSrc":"","name":"锁骨链","price":1497},{"id":4,"image":"/uploads/stock/jewelry_13.jpg","imageSrc":"","name":"吊坠项链","price":2096}], productWidgets: [{"key":"products_jewelry_v01_cart_03","cid":"jewelry_v01_cart_03","limit":4}], notice_jewelry_v01_cart_02: {"bgColor":"#fff8e1","content":"满99元包邮，请在结算前确认商品信息","duration":"5.50s","fontSize":26,"prefixTitle":"","scrollDirection":"left","scrollSpeed":40,"showIcon":true,"textColor":"#666","trackClass":"to-left"}, noticeWidgets: [{"key":"notice_jewelry_v01_cart_02","cid":"jewelry_v01_cart_02"}], cartLoggedIn: false, cartList: [], cartTotal: 0, cartSubtitle: "共0件商品" },
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
async loadCartPage() {
    const { req, assetUrl } = require('../../utils/api');
    try {
      const j = await req('/cart/list.php');
      if (j.code !== 0 || !j.data || j.data.logged_in === false) {
        this.setData({ cartLoggedIn: false, cartList: [], cartTotal: 0, cartSubtitle: '共0件商品' });
        return;
      }
      const list = (j.data.list || []).map(function(it) {
        return Object.assign({}, it, { image: assetUrl(it.image) });
      });
      const qty = list.reduce(function(sum, it) { return sum + (parseInt(it.quantity, 10) || 0); }, 0);
      this.setData({
        cartLoggedIn: true,
        cartList: list,
        cartTotal: j.data.selected_total || 0,
        cartSubtitle: '共' + qty + '件商品'
      });
    } catch (e) {
      this.setData({ cartLoggedIn: false, cartList: [], cartTotal: 0, cartSubtitle: '共0件商品' });
    }
  },
  async doCartLogin() {
    const { wxLoginWithProfile, toastMsg } = require('../../utils/api');
    const j = await wxLoginWithProfile();
    wx.showToast({ title: toastMsg(j, '登录成功', '登录失败'), icon: 'none' });
    if (j.code === 0) this.loadCartPage();
  },
  async changeCartQty(e) {
    const { req } = require('../../utils/api');
    const qty = e.currentTarget.dataset.qty;
    if (qty < 1) return;
    await req('/cart/update.php', 'POST', { id: e.currentTarget.dataset.id, quantity: qty });
    this.loadCartPage();
  },
  async toggleCartItem(e) {
    const { req } = require('../../utils/api');
    const id = e.currentTarget.dataset.id;
    const item = (this.data.cartList || []).find(function(x) { return x.id === id; });
    await req('/cart/update.php', 'POST', { id: id, selected: item && item.selected ? 0 : 1 });
    this.loadCartPage();
  },
  async removeCartItem(e) {
    const { req } = require('../../utils/api');
    await req('/cart/remove.php', 'POST', { id: e.currentTarget.dataset.id });
    this.loadCartPage();
  },
  goCartCheckout() {
    wx.navigateTo({ url: '/packageSys/pages/checkout/checkout?from_cart=1' });
  },
goProduct(e) {
    wx.navigateTo({ url: '/packageSys/pages/product-detail/product-detail?id=' + e.currentTarget.dataset.id });
  },
  goProductList(e) {
    var key = (e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.key) || '';
    var widgets = this.data.productWidgets || [];
    var cfg = null;
    for (var i = 0; i < widgets.length; i++) {
      if (!key || widgets[i].key === key) { cfg = widgets[i]; break; }
    }
    var url = '/packageSys/pages/product-list/product-list?title=' + encodeURIComponent('商品列表');
    if (cfg && cfg.cid) url += '&component_id=' + encodeURIComponent(cfg.cid);
    wx.navigateTo({ url: url });
  },
  async addCartFromList(e) {
    const id = e.currentTarget.dataset.id;
    const { req, toastMsg, showAppModal } = require('../../utils/api');
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
    const { req, toastMsg } = require('../../utils/api');
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
resolveListImages(list, field) {
    const { assetUrl } = require('../../utils/api');
    field = field || 'image';
    return (list || []).map(function(it) {
      var o = Object.assign({}, it);
      o[field + 'Src'] = assetUrl(it[field] || '');
      return o;
    });
  },
  bootstrapWidgetImages() {
    const patch = {};
    try {
      (this.data.productWidgets || []).forEach(function(cfg) {
        const cur = this.data[cfg.key];
        if (cur && cur.length) patch[cfg.key] = this.resolveListImages(cur, 'image');
      }.bind(this));
    } catch (e) { this.mpDevWarn('bootstrap', 'products', e); }
    try {
      (this.data.articleWidgets || []).forEach(function(cfg) {
        const cur = this.data[cfg.key];
        if (cur && cur.length) patch[cfg.key] = this.resolveListImages(cur, 'cover');
      }.bind(this));
    } catch (e) { this.mpDevWarn('bootstrap', 'articles', e); }
    try {
      (this.data.swiperWidgets || []).forEach(function(cfg) {
        const w = this.data[cfg.key];
        if (w && w.items && w.items.length) {
          const next = Object.assign({}, w);
          next.items = this.resolveListImages(w.items, 'image');
          patch[cfg.key] = next;
        }
      }.bind(this));
    } catch (e) { this.mpDevWarn('bootstrap', 'swiper', e); }
    if (Object.keys(patch).length) this.setData(patch);
  },
async loadProducts() {
    await this._mpLoadProducts_products_jewelry_v01_cart_03()
  },
  async _mpLoadProducts_products_jewelry_v01_cart_03() {
    const { req, assetUrl } = require('../../utils/api');
    var url = '/product/list.php?limit=4&component_id=' + encodeURIComponent("jewelry_v01_cart_03");
    try {
      var j = await req(url);
      var list = [];
      if (j && j.code === 0 && j.data && j.data.list && j.data.list.length) {
        list = j.data.list.map(function(p, idx) {
          var o = Object.assign({}, p);
          o.id = o.id != null ? String(o.id) : String(idx);
          o.imageSrc = assetUrl(o.image || '');
          return o;
        });
        if (list.length > 4) list = list.slice(0, 4);
      } else {
        list = (this.data.products_jewelry_v01_cart_03 || []).slice();
      }
      if (!list.length) return;
      this.setData({ products_jewelry_v01_cart_03: list });
    } catch (e) { this.mpDevWarn('product', "jewelry_v01_cart_03", e); }
  },
noticeDuration(speed) {
    speed = speed || 50;
    if (speed < 10) speed = 10;
    if (speed > 200) speed = 200;
    return (220 / speed).toFixed(2) + 's';
  },
  noticeTrackClass(dir) {
    return dir === 'right' ? 'to-right' : 'to-left';
  },
  async loadNotices() {
    await this._mpLoadNotice_notice_jewelry_v01_cart_02()
  },
  async _mpLoadNotice_notice_jewelry_v01_cart_02() {
    const { req } = require('../../utils/api');
    try {
      var j = await req('/notice/get.php?id=' + encodeURIComponent("jewelry_v01_cart_02"));
      if (!j || j.code !== 0 || !j.data || !j.data.content) return;
      var d = j.data;
      var speed = d.scrollSpeed || 50;
      var dir = d.scrollDirection || 'left';
      this.setData({
        notice_jewelry_v01_cart_02: {
          content: d.content || '',
          textColor: d.textColor || '#333333',
          bgColor: d.bgColor || '#ffffff',
          fontSize: d.fontSize || 28,
          scrollDirection: dir,
          scrollSpeed: speed,
          trackClass: this.noticeTrackClass(dir),
          duration: this.noticeDuration(speed),
          showIcon: d.showIcon !== false,
          prefixTitle: d.prefixTitle || ''
        }
      });
    } catch (e) { this.mpDevWarn('notice', "jewelry_v01_cart_02", e); }
  },
mpDevWarn(kind, cid, err) {
    const { mpDevWarn } = require('../../utils/api');
    mpDevWarn(kind, cid, err);
  },
seedDemoImages() {
    const { assetUrl } = require('../../utils/api');
    var withSrc = function(list, field) {
      var srcKey = field + 'Src';
      return (list || []).map(function(it) {
        var o = Object.assign({}, it);
        if (!o[srcKey] && o[field]) o[srcKey] = assetUrl(o[field]);
        return o;
      });
    };
    { var list = withSrc(this.data.products_jewelry_v01_cart_03, 'image'); if (list.length) this.setData({ products_jewelry_v01_cart_03: list }); }
  },
onShow() {
    if (this.loadCartPage) this.loadCartPage().catch(function(){});
    if (this.loadNotices) this.loadNotices().catch(function(){});
    if (this.loadProducts) this.loadProducts().catch(function(){});
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