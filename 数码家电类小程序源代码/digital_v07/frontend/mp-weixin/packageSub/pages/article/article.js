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
  data: { appModalShow: false, appModalTitle: '提示', appModalContent: '',  siteRoot: __mpSiteRoot, assetRoot: __mpAssetRoot, articles_digital_v07_article_02: [{"cover":"/uploads/stock/corporate_7.jpg","coverSrc":"","created_at":"2026-01-01","id":"demo_1","summary":"部署后可在后台编辑","title":"欢迎来到集团官网"},{"cover":"/uploads/stock/corporate_8.jpg","coverSrc":"","created_at":"2026-01-02","id":"demo_2","summary":"演示文章仅供参考","title":"集团官网新品发布"},{"cover":"/uploads/stock/corporate_9.jpg","coverSrc":"","created_at":"2026-01-03","id":"demo_3","summary":"演示数据","title":"会员权益说明"},{"cover":"/uploads/stock/corporate_48.jpg","coverSrc":"","created_at":"2026-01-04","id":"demo_4","summary":"演示数据","title":"服务与配送说明"},{"cover":"/uploads/stock/corporate_49.jpg","coverSrc":"","created_at":"2026-01-05","id":"demo_5","summary":"演示数据","title":"常见问题解答"}], articleWidgets: [{"key":"articles_digital_v07_article_02","cid":"digital_v07_article_02","limit":5}], swiper_digital_v07_article_03: { height: 280, interval: 4000, autoplay: true, items: [{"image":"/uploads/stock/digital_50.jpg","imageSrc":"","link":"","title":"资讯"}] }, swiperWidgets: [{"key":"swiper_digital_v07_article_03","cid":"digital_v07_article_03","height":280,"interval":4000}], showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#795548", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}] },
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
async loadSwipers() {
    await this._mpLoadSwiper_swiper_digital_v07_article_03()
  },
  async _mpLoadSwiper_swiper_digital_v07_article_03() {
    const { req, assetUrl } = require('../../../utils/api');
    try {
      var j = await req('/swiper/list.php?id=' + encodeURIComponent("digital_v07_article_03"));
      if (!j || j.code !== 0 || !j.data) return;
      var d = j.data;
      var items = (d.items || []).map(function(it, idx) {
        var o = Object.assign({}, it);
        o.imageSrc = assetUrl(o.image || '');
        return o;
      });
      if (!items.length) return;
      var cur = this.data.swiper_digital_v07_article_03 || {};
      this.setData({
        swiper_digital_v07_article_03: {
          height: d.height || cur.height || 280,
          interval: d.interval || cur.interval || 4000,
          autoplay: d.autoplay !== false,
          items: items
        }
      });
    } catch (e) { this.mpDevWarn('swiper', "digital_v07_article_03", e); }
  },
mpDevWarn(kind, cid, err) {
    const { mpDevWarn } = require('../../../utils/api');
    mpDevWarn(kind, cid, err);
  },
seedDemoImages() {
    const { assetUrl } = require('../../../utils/api');
    var withSrc = function(list, field) {
      var srcKey = field + 'Src';
      return (list || []).map(function(it) {
        var o = Object.assign({}, it);
        if (!o[srcKey] && o[field]) o[srcKey] = assetUrl(o[field]);
        return o;
      });
    };
    { var list = withSrc(this.data.articles_digital_v07_article_02, 'cover'); if (list.length) this.setData({ articles_digital_v07_article_02: list }); }
    { var wgt = this.data.swiper_digital_v07_article_03; if (wgt && wgt.items && wgt.items.length) { var next = Object.assign({}, wgt, { items: withSrc(wgt.items, 'image') }); this.setData({ swiper_digital_v07_article_03: next }); } }
  },
goArticle(e) {
    var id = e.currentTarget.dataset.id;
    if (!id) return;
    wx.navigateTo({ url: '/packageSys/pages/article-detail/article-detail?id=' + id });
  },
  goArticleList(e) {
    var key = (e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.key) || '';
    var widgets = this.data.articleWidgets || [];
    var cfg = null;
    for (var i = 0; i < widgets.length; i++) {
      if (!key || widgets[i].key === key) { cfg = widgets[i]; break; }
    }
    var title = '文章列表';
    var url = '/packageSys/pages/article-list/article-list?title=' + encodeURIComponent(title);
    if (cfg && cfg.cid) url += '&component_id=' + encodeURIComponent(cfg.cid);
    wx.navigateTo({ url: url });
  },
async loadArticles() {
    if (this._articleLoading) return;
    this._articleLoading = true;
    try {
      await this._mpLoadArticles_articles_digital_v07_article_02()
    } finally {
      this._articleLoading = false;
    }
  },
  async _mpLoadArticles_articles_digital_v07_article_02() {
    const { req, assetUrl } = require('../../../utils/api');
    var url = '/article/list.php?limit=5&component_id=' + encodeURIComponent("digital_v07_article_02");
    try {
      var j = await req(url);
      var list = [];
      if (j && j.code === 0 && j.data && j.data.list && j.data.list.length) {
        list = j.data.list.map(function(a, idx) {
          var o = Object.assign({}, a);
          o.id = o.id != null ? String(o.id) : String(idx);
          o.coverSrc = assetUrl(o.cover || '');
          return o;
        });
      } else {
        list = (this.data.articles_digital_v07_article_02 || []).slice();
      }
      if (!list.length) return;
      this.setData({ articles_digital_v07_article_02: list });
    } catch (e) {
      this.mpDevWarn('article', "digital_v07_article_02", e);
      var cur = this.data.articles_digital_v07_article_02;
      if (cur && cur.length) {
        try {
          var patched = cur.map(function(it) {
            var o = Object.assign({}, it);
            if (!o.coverSrc && o.cover) o.coverSrc = assetUrl(o.cover);
            return o;
          });
          this.setData({ articles_digital_v07_article_02: patched });
        } catch (e2) {}
      }
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
onShow() {
    if (this.loadSwipers) this.loadSwipers().catch(function(){});
    if (this.loadArticles) this.loadArticles().catch(function(){});
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