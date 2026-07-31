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
  data: { appModalShow: false, appModalTitle: '提示', appModalContent: '',  siteRoot: __mpSiteRoot, assetRoot: __mpAssetRoot, products_software_it_v03_cat_04: [{"id":1,"image":"/uploads/stock/software_it_10.jpg","imageSrc":"","name":"云智科技精选款A","price":39},{"id":2,"image":"/uploads/stock/software_it_11.jpg","imageSrc":"","name":"云智科技精选款B","price":88},{"id":3,"image":"/uploads/stock/software_it_12.jpg","imageSrc":"","name":"云智科技精选款C","price":137},{"id":4,"image":"/uploads/stock/software_it_13.jpg","imageSrc":"","name":"云智科技精选款D","price":186},{"id":5,"image":"/uploads/stock/software_it_14.jpg","imageSrc":"","name":"云智科技精选款E","price":235},{"id":6,"image":"/uploads/stock/software_it_15.jpg","imageSrc":"","name":"云智科技精选款F","price":284},{"id":7,"image":"/uploads/stock/software_it_16.jpg","imageSrc":"","name":"热销单品1","price":39},{"id":8,"image":"/uploads/stock/software_it_17.jpg","imageSrc":"","name":"热销单品2","price":88}], productWidgets: [{"key":"products_software_it_v03_cat_04","cid":"software_it_v03_cat_04","limit":8}], gridNav_software_it_v03_cat_03: {"items":[{"icon":"/uploads/stock/software_it_1.jpg","iconSrc":"","navUrl":"","text":"定制开发"},{"icon":"/uploads/stock/software_it_2.jpg","iconSrc":"","navUrl":"","text":"小程序"},{"icon":"/uploads/stock/software_it_3.jpg","iconSrc":"","navUrl":"","text":"APP"},{"icon":"/uploads/stock/software_it_4.jpg","iconSrc":"","navUrl":"","text":"网站建设"},{"icon":"/uploads/stock/software_it_5.jpg","iconSrc":"","navUrl":"","text":"运维"},{"icon":"/uploads/stock/software_it_6.jpg","iconSrc":"","navUrl":"","text":"云服务"},{"icon":"/uploads/stock/software_it_55.jpg","iconSrc":"","navUrl":"","text":"数据"},{"icon":"/uploads/stock/software_it_56.jpg","iconSrc":"","navUrl":"","text":"咨询"},{"icon":"/uploads/stock/software_it_57.jpg","iconSrc":"","navUrl":"","text":"案例"},{"icon":"/uploads/stock/software_it_58.jpg","iconSrc":"","navUrl":"","text":"更多"}]} },
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
    await this._mpLoadProducts_products_software_it_v03_cat_04()
  },
  async _mpLoadProducts_products_software_it_v03_cat_04() {
    const { req, assetUrl } = require('../../utils/api');
    var url = '/product/list.php?limit=8&component_id=' + encodeURIComponent("software_it_v03_cat_04");
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
        list = (this.data.products_software_it_v03_cat_04 || []).slice();
      }
      if (!list.length) return;
      this.setData({ products_software_it_v03_cat_04: list });
    } catch (e) { this.mpDevWarn('product', "software_it_v03_cat_04", e); }
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
    { var list = withSrc(this.data.products_software_it_v03_cat_04, 'image'); if (list.length) this.setData({ products_software_it_v03_cat_04: list }); }
  },
onShow() {
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