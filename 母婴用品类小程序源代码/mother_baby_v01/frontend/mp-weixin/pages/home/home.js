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
  data: { appModalShow: false, appModalTitle: '提示', appModalContent: '',  siteRoot: __mpSiteRoot, assetRoot: __mpAssetRoot, products_mother_baby_v01_home_11: [{"id":1,"image":"/uploads/stock/mother_baby_10.jpg","imageSrc":"","name":"婴儿配方奶粉1段","price":49},{"id":2,"image":"/uploads/stock/mother_baby_11.jpg","imageSrc":"","name":"婴儿配方奶粉2段","price":118},{"id":3,"image":"/uploads/stock/mother_baby_12.jpg","imageSrc":"","name":"有机奶粉","price":187}], products_mother_baby_v01_home_13: [{"id":7,"image":"/uploads/stock/mother_baby_16.jpg","imageSrc":"","name":"纸尿裤S码","price":49},{"id":8,"image":"/uploads/stock/mother_baby_17.jpg","imageSrc":"","name":"纸尿裤M码","price":118},{"id":9,"image":"/uploads/stock/mother_baby_18.jpg","imageSrc":"","name":"纸尿裤L码","price":187},{"id":10,"image":"/uploads/stock/mother_baby_19.jpg","imageSrc":"","name":"拉拉裤","price":256},{"id":11,"image":"/uploads/stock/mother_baby_20.jpg","imageSrc":"","name":"湿巾","price":325},{"id":12,"image":"/uploads/stock/mother_baby_21.jpg","imageSrc":"","name":"棉柔巾","price":394}], productWidgets: [{"key":"products_mother_baby_v01_home_11","cid":"mother_baby_v01_home_11","limit":3},{"key":"products_mother_baby_v01_home_13","cid":"mother_baby_v01_home_13","limit":6}], swiper_mother_baby_v01_home_03: { height: 320, interval: 3000, autoplay: true, items: [{"image":"/uploads/stock/mother_baby_7.jpg","imageSrc":"","link":"","title":"母婴用品"},{"image":"/uploads/stock/mother_baby_8.jpg","imageSrc":"","link":"","title":"孕妈专享"},{"image":"/uploads/stock/mother_baby_9.jpg","imageSrc":"","link":"","title":"宝宝好物"}] }, swiperWidgets: [{"key":"swiper_mother_baby_v01_home_03","cid":"mother_baby_v01_home_03","height":320,"interval":3000}], notice_mother_baby_v01_home_04: {"bgColor":"#ffffff","content":"新用户专享优惠，欢迎选购母婴用品好物！","duration":"4.40s","fontSize":28,"prefixTitle":"","scrollDirection":"left","scrollSpeed":50,"showIcon":true,"textColor":"#333333","trackClass":"to-left"}, noticeWidgets: [{"key":"notice_mother_baby_v01_home_04","cid":"mother_baby_v01_home_04"}], gridNav_mother_baby_v01_home_02: {"items":[{"icon":"/uploads/stock/mother_baby_1.jpg","iconSrc":"","navUrl":"","text":"再买一单"},{"icon":"/uploads/stock/mother_baby_2.jpg","iconSrc":"","navUrl":"","text":"会员中心"},{"icon":"/uploads/stock/mother_baby_3.jpg","iconSrc":"","navUrl":"","text":"优惠券"},{"icon":"/uploads/stock/mother_baby_4.jpg","iconSrc":"","navUrl":"","text":"我的订单"}]}, gridNav_mother_baby_v01_home_05: {"items":[{"icon":"/uploads/stock/mother_baby_1.jpg","iconSrc":"","navUrl":"","text":"极速达"},{"icon":"/uploads/stock/mother_baby_2.jpg","iconSrc":"","navUrl":"","text":"品质保障"},{"icon":"/uploads/stock/mother_baby_3.jpg","iconSrc":"","navUrl":"","text":"售后无忧"}]}, gridNav_mother_baby_v01_home_06: {"items":[{"icon":"/uploads/stock/mother_baby_1.jpg","iconSrc":"","navUrl":"","text":"奶粉"},{"icon":"/uploads/stock/mother_baby_2.jpg","iconSrc":"","navUrl":"","text":"尿裤"},{"icon":"/uploads/stock/mother_baby_3.jpg","iconSrc":"","navUrl":"","text":"辅食"},{"icon":"/uploads/stock/mother_baby_4.jpg","iconSrc":"","navUrl":"","text":"玩具"},{"icon":"/uploads/stock/mother_baby_5.jpg","iconSrc":"","navUrl":"","text":"童装"},{"icon":"/uploads/stock/mother_baby_6.jpg","iconSrc":"","navUrl":"","text":"推车"},{"icon":"/uploads/stock/mother_baby_55.jpg","iconSrc":"","navUrl":"","text":"洗护"},{"icon":"/uploads/stock/mother_baby_56.jpg","iconSrc":"","navUrl":"","text":"孕妈"},{"icon":"/uploads/stock/mother_baby_57.jpg","iconSrc":"","navUrl":"","text":"绘本"},{"icon":"/uploads/stock/mother_baby_58.jpg","iconSrc":"","navUrl":"","text":"更多"}]}, promoPair_mother_baby_v01_home_07: {"items":[{"bgColor":"#e8f8f0","image":"/uploads/stock/mother_baby_40.jpg","imageSrc":"","navUrl":"","title":"孕妈专享"},{"bgColor":"#fff3e0","image":"/uploads/stock/mother_baby_41.jpg","imageSrc":"","navUrl":"","title":"宝宝好物"}]}, promoPair_mother_baby_v01_home_08: {"items":[{"bgColor":"#e8f8f0","image":"/uploads/stock/mother_baby_42.jpg","imageSrc":"","navUrl":"","title":"会员礼包"},{"bgColor":"#fff3e0","image":"/uploads/stock/mother_baby_43.jpg","imageSrc":"","navUrl":"","title":"限时特惠"}]} },
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
onNavTap(e) {
    const raw = e.currentTarget.dataset.url;
    if (!raw) return;
    if (raw.indexOf('http://') === 0 || raw.indexOf('https://') === 0) {
      wx.setClipboardData({ data: raw, success() { wx.showModal({ title: '提示', content: '链接已复制', showCancel: false }); } });
      return;
    }
    if (raw.indexOf('switchTab:') === 0) {
      const path = raw.slice(10);
      wx.switchTab({ url: path, fail() { wx.reLaunch({ url: path }); } });
      return;
    }
    wx.navigateTo({ url: raw, fail() { wx.showToast({ title: '页面打开失败', icon: 'none' }); } });
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
    await this._mpLoadProducts_products_mother_baby_v01_home_11();
    await this._mpLoadProducts_products_mother_baby_v01_home_13()
  },
  async _mpLoadProducts_products_mother_baby_v01_home_11() {
    const { req, assetUrl } = require('../../utils/api');
    var url = '/product/list.php?limit=3&component_id=' + encodeURIComponent("mother_baby_v01_home_11");
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
        if (list.length > 3) list = list.slice(0, 3);
      } else {
        list = (this.data.products_mother_baby_v01_home_11 || []).slice();
      }
      if (!list.length) return;
      this.setData({ products_mother_baby_v01_home_11: list });
    } catch (e) { this.mpDevWarn('product', "mother_baby_v01_home_11", e); }
  },

  async _mpLoadProducts_products_mother_baby_v01_home_13() {
    const { req, assetUrl } = require('../../utils/api');
    var url = '/product/list.php?limit=6&component_id=' + encodeURIComponent("mother_baby_v01_home_13");
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
        if (list.length > 6) list = list.slice(0, 6);
      } else {
        list = (this.data.products_mother_baby_v01_home_13 || []).slice();
      }
      if (!list.length) return;
      this.setData({ products_mother_baby_v01_home_13: list });
    } catch (e) { this.mpDevWarn('product', "mother_baby_v01_home_13", e); }
  },
resolveGridNavPromoImages() {
    const { assetUrl } = require('../../utils/api');
    const patch = {};
    const data = this.data || {};
    Object.keys(data).forEach(function(k) {
      if (k.indexOf('gridNav_') === 0 || k.indexOf('promoPair_') === 0) {
        const block = data[k];
        if (!block || !Array.isArray(block.items)) return;
        const items = block.items.map(function(it) {
          const o = Object.assign({}, it);
          if (k.indexOf('gridNav_') === 0 && o.icon) {
            o.iconSrc = assetUrl(o.icon);
          }
          if (k.indexOf('promoPair_') === 0 && o.image) {
            o.imageSrc = assetUrl(o.image);
          }
          return o;
        });
        patch[k] = Object.assign({}, block, { items: items });
      }
    });
    if (Object.keys(patch).length) this.setData(patch);
  },
async loadSwipers() {
    await this._mpLoadSwiper_swiper_mother_baby_v01_home_03()
  },
  async _mpLoadSwiper_swiper_mother_baby_v01_home_03() {
    const { req, assetUrl } = require('../../utils/api');
    try {
      var j = await req('/swiper/list.php?id=' + encodeURIComponent("mother_baby_v01_home_03"));
      if (!j || j.code !== 0 || !j.data) return;
      var d = j.data;
      var items = (d.items || []).map(function(it, idx) {
        var o = Object.assign({}, it);
        o.imageSrc = assetUrl(o.image || '');
        return o;
      });
      if (!items.length) return;
      var cur = this.data.swiper_mother_baby_v01_home_03 || {};
      this.setData({
        swiper_mother_baby_v01_home_03: {
          height: d.height || cur.height || 320,
          interval: d.interval || cur.interval || 3000,
          autoplay: d.autoplay !== false,
          items: items
        }
      });
    } catch (e) { this.mpDevWarn('swiper', "mother_baby_v01_home_03", e); }
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
    await this._mpLoadNotice_notice_mother_baby_v01_home_04()
  },
  async _mpLoadNotice_notice_mother_baby_v01_home_04() {
    const { req } = require('../../utils/api');
    try {
      var j = await req('/notice/get.php?id=' + encodeURIComponent("mother_baby_v01_home_04"));
      if (!j || j.code !== 0 || !j.data || !j.data.content) return;
      var d = j.data;
      var speed = d.scrollSpeed || 50;
      var dir = d.scrollDirection || 'left';
      this.setData({
        notice_mother_baby_v01_home_04: {
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
    } catch (e) { this.mpDevWarn('notice', "mother_baby_v01_home_04", e); }
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
    { var list = withSrc(this.data.products_mother_baby_v01_home_11, 'image'); if (list.length) this.setData({ products_mother_baby_v01_home_11: list }); }
    { var list = withSrc(this.data.products_mother_baby_v01_home_13, 'image'); if (list.length) this.setData({ products_mother_baby_v01_home_13: list }); }
    { var wgt = this.data.swiper_mother_baby_v01_home_03; if (wgt && wgt.items && wgt.items.length) { var next = Object.assign({}, wgt, { items: withSrc(wgt.items, 'image') }); this.setData({ swiper_mother_baby_v01_home_03: next }); } }
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