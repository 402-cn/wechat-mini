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
  data: { appModalShow: false, appModalTitle: '提示', appModalContent: '',  siteRoot: __mpSiteRoot, assetRoot: __mpAssetRoot, products_digital_v05_home_04: [{"id":1,"image":"/uploads/stock/digital_10.jpg","imageSrc":"","name":"智能手机A","price":199},{"id":2,"image":"/uploads/stock/digital_11.jpg","imageSrc":"","name":"智能手机B","price":498},{"id":3,"image":"/uploads/stock/digital_12.jpg","imageSrc":"","name":"手机壳","price":797},{"id":4,"image":"/uploads/stock/digital_13.jpg","imageSrc":"","name":"钢化膜","price":1096},{"id":5,"image":"/uploads/stock/digital_14.jpg","imageSrc":"","name":"快充头","price":1395},{"id":6,"image":"/uploads/stock/digital_15.jpg","imageSrc":"","name":"数据线","price":1694},{"id":7,"image":"/uploads/stock/digital_16.jpg","imageSrc":"","name":"轻薄笔记本","price":199},{"id":8,"image":"/uploads/stock/digital_17.jpg","imageSrc":"","name":"游戏本","price":498}], products_digital_v05_home_06: [{"id":9,"image":"/uploads/stock/digital_18.jpg","imageSrc":"","name":"平板电脑","price":797},{"id":10,"image":"/uploads/stock/digital_19.jpg","imageSrc":"","name":"机械键盘","price":1096},{"id":11,"image":"/uploads/stock/digital_20.jpg","imageSrc":"","name":"无线鼠标","price":1395},{"id":12,"image":"/uploads/stock/digital_21.jpg","imageSrc":"","name":"显示器","price":1694}], productWidgets: [{"key":"products_digital_v05_home_04","cid":"digital_v05_home_04","limit":8},{"key":"products_digital_v05_home_06","cid":"digital_v05_home_06","limit":4}], swiper_digital_v05_home_02: { height: 360, interval: 3000, autoplay: true, items: [{"image":"/uploads/stock/digital_49.jpg","imageSrc":"","link":"","title":"数码家电"}] }, swiperWidgets: [{"key":"swiper_digital_v05_home_02","cid":"digital_v05_home_02","height":360,"interval":3000}], notice_digital_v05_home_03: {"bgColor":"#ffffff","content":"新用户专享优惠，欢迎选购数码家电好物！","duration":"4.40s","fontSize":28,"prefixTitle":"","scrollDirection":"left","scrollSpeed":50,"showIcon":true,"textColor":"#333333","trackClass":"to-left"}, noticeWidgets: [{"key":"notice_digital_v05_home_03","cid":"digital_v05_home_03"}] },
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
    await this._mpLoadProducts_products_digital_v05_home_04();
    await this._mpLoadProducts_products_digital_v05_home_06()
  },
  async _mpLoadProducts_products_digital_v05_home_04() {
    const { req, assetUrl } = require('../../utils/api');
    var url = '/product/list.php?limit=8&component_id=' + encodeURIComponent("digital_v05_home_04");
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
        if (list.length > 8) list = list.slice(0, 8);
      } else {
        list = (this.data.products_digital_v05_home_04 || []).slice();
      }
      if (!list.length) return;
      this.setData({ products_digital_v05_home_04: list });
    } catch (e) { this.mpDevWarn('product', "digital_v05_home_04", e); }
  },

  async _mpLoadProducts_products_digital_v05_home_06() {
    const { req, assetUrl } = require('../../utils/api');
    var url = '/product/list.php?limit=4&component_id=' + encodeURIComponent("digital_v05_home_06");
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
        list = (this.data.products_digital_v05_home_06 || []).slice();
      }
      if (!list.length) return;
      this.setData({ products_digital_v05_home_06: list });
    } catch (e) { this.mpDevWarn('product', "digital_v05_home_06", e); }
  },
async loadSwipers() {
    await this._mpLoadSwiper_swiper_digital_v05_home_02()
  },
  async _mpLoadSwiper_swiper_digital_v05_home_02() {
    const { req, assetUrl } = require('../../utils/api');
    try {
      var j = await req('/swiper/list.php?id=' + encodeURIComponent("digital_v05_home_02"));
      if (!j || j.code !== 0 || !j.data) return;
      var d = j.data;
      var items = (d.items || []).map(function(it, idx) {
        var o = Object.assign({}, it);
        o.imageSrc = assetUrl(o.image || '');
        return o;
      });
      if (!items.length) return;
      var cur = this.data.swiper_digital_v05_home_02 || {};
      this.setData({
        swiper_digital_v05_home_02: {
          height: d.height || cur.height || 360,
          interval: d.interval || cur.interval || 3000,
          autoplay: d.autoplay !== false,
          items: items
        }
      });
    } catch (e) { this.mpDevWarn('swiper', "digital_v05_home_02", e); }
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
    await this._mpLoadNotice_notice_digital_v05_home_03()
  },
  async _mpLoadNotice_notice_digital_v05_home_03() {
    const { req } = require('../../utils/api');
    try {
      var j = await req('/notice/get.php?id=' + encodeURIComponent("digital_v05_home_03"));
      if (!j || j.code !== 0 || !j.data || !j.data.content) return;
      var d = j.data;
      var speed = d.scrollSpeed || 50;
      var dir = d.scrollDirection || 'left';
      this.setData({
        notice_digital_v05_home_03: {
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
    } catch (e) { this.mpDevWarn('notice', "digital_v05_home_03", e); }
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
    { var list = withSrc(this.data.products_digital_v05_home_04, 'image'); if (list.length) this.setData({ products_digital_v05_home_04: list }); }
    { var list = withSrc(this.data.products_digital_v05_home_06, 'image'); if (list.length) this.setData({ products_digital_v05_home_06: list }); }
    { var wgt = this.data.swiper_digital_v05_home_02; if (wgt && wgt.items && wgt.items.length) { var next = Object.assign({}, wgt, { items: withSrc(wgt.items, 'image') }); this.setData({ swiper_digital_v05_home_02: next }); } }
  },
onShow() {
    if (this.loadNotices) this.loadNotices().catch(function(){});
    if (this.loadSwipers) this.loadSwipers().catch(function(){});
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