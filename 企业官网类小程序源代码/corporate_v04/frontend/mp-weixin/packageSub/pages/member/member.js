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
  data: { appModalShow: false, appModalTitle: '提示', appModalContent: '',  siteRoot: __mpSiteRoot, assetRoot: __mpAssetRoot, gridNav_corporate_v04_member_03: {"items":[{"icon":"/uploads/stock/corporate_1.jpg","iconSrc":"","navUrl":"","text":"会员折扣"},{"icon":"/uploads/stock/corporate_2.jpg","iconSrc":"","navUrl":"","text":"积分翻倍"},{"icon":"/uploads/stock/corporate_3.jpg","iconSrc":"","navUrl":"","text":"专属客服"}]}, showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#1abc9c", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}] },
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
resolveGridNavPromoImages() {
    const { assetUrl } = require('../../../utils/api');
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