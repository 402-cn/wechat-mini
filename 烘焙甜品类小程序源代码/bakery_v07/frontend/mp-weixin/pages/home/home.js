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
  data: { appModalShow: false, appModalTitle: '提示', appModalContent: '',  siteRoot: __mpSiteRoot, assetRoot: __mpAssetRoot, articles_bakery_v07_home_06: [{"cover":"/uploads/stock/corporate_7.jpg","coverSrc":"","created_at":"2026-01-01","id":"demo_1","summary":"部署后可在后台编辑","title":"欢迎来到集团官网"},{"cover":"/uploads/stock/corporate_8.jpg","coverSrc":"","created_at":"2026-01-02","id":"demo_2","summary":"演示文章仅供参考","title":"集团官网新品发布"},{"cover":"/uploads/stock/corporate_9.jpg","coverSrc":"","created_at":"2026-01-03","id":"demo_3","summary":"演示数据","title":"会员权益说明"},{"cover":"/uploads/stock/corporate_48.jpg","coverSrc":"","created_at":"2026-01-04","id":"demo_4","summary":"演示数据","title":"服务与配送说明"}], articleWidgets: [{"key":"articles_bakery_v07_home_06","cid":"bakery_v07_home_06","limit":4}], gridNav_bakery_v07_home_04: {"items":[{"icon":"/uploads/stock/bakery_1.jpg","iconSrc":"","navUrl":"/packageSys/pages/search-article/search-article","text":"公司概况"},{"icon":"/uploads/stock/bakery_2.jpg","iconSrc":"","navUrl":"switchTab:/pages/category/category","text":"产品中心"},{"icon":"/uploads/stock/bakery_3.jpg","iconSrc":"","navUrl":"/packageSys/pages/search-article/search-article","text":"新闻资讯"},{"icon":"/uploads/stock/bakery_4.jpg","iconSrc":"","navUrl":"/packageSub/pages/contact/contact","text":"联系我们"}]} },
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
    { var list = withSrc(this.data.articles_bakery_v07_home_06, 'cover'); if (list.length) this.setData({ articles_bakery_v07_home_06: list }); }
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
      await this._mpLoadArticles_articles_bakery_v07_home_06()
    } finally {
      this._articleLoading = false;
    }
  },
  async _mpLoadArticles_articles_bakery_v07_home_06() {
    const { req, assetUrl } = require('../../utils/api');
    var url = '/article/list.php?limit=4&component_id=' + encodeURIComponent("bakery_v07_home_06");
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
        list = (this.data.articles_bakery_v07_home_06 || []).slice();
      }
      if (!list.length) return;
      this.setData({ articles_bakery_v07_home_06: list });
    } catch (e) {
      this.mpDevWarn('article', "bakery_v07_home_06", e);
      var cur = this.data.articles_bakery_v07_home_06;
      if (cur && cur.length) {
        try {
          var patched = cur.map(function(it) {
            var o = Object.assign({}, it);
            if (!o.coverSrc && o.cover) o.coverSrc = assetUrl(o.cover);
            return o;
          });
          this.setData({ articles_bakery_v07_home_06: patched });
        } catch (e2) {}
      }
    }
  },
onShow() {
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