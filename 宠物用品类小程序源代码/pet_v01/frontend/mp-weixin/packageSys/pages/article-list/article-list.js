const { req, assetUrl } = require('../../../utils/api');
Page({
  data: { showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#16a085", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}],  appModalShow: false, appModalTitle: '提示', appModalContent: '',  articleFullLayout: 'image-text', articleShowCover: true, articleCategoryId: 0, articleCategories: [], articleListItems: [], articleListPage: 1, articleListHasMore: true, articleListLoading: false },
  onLoad(q) {
    if (q && q.title) wx.setNavigationBarTitle({ title: decodeURIComponent(q.title) });
  },
  onShow() { this.initArticleFullPage(); },
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
  onReachBottom() {
    if (this.loadArticleFullPageMore) this.loadArticleFullPageMore();
  },
  async loadArticleCategories() {
    const { req } = require('../../../utils/api');
    try {
      const j = await req('/article/categories.php');
      if (j.code === 0) this.setData({ articleCategories: j.data.list || [] });
    } catch (e) {}
  },
  async loadArticleFullPage(reset) {
    const { req, assetUrl } = require('../../../utils/api');
    if (this.data.articleListLoading) return;
    let page = reset ? 1 : (this.data.articleListPage || 1);
    if (!reset && !this.data.articleListHasMore) return;
    this.setData({ articleListLoading: true });
    let url = '/article/list.php?page=' + page + '&page_size=20';
    if (this.data.articleCategoryId > 0) url += '&category_id=' + this.data.articleCategoryId;
    try {
      const j = await req(url);
      if (j.code !== 0) {
        this.setData({ articleListLoading: false });
        return;
      }
      const list = (j.data.list || []).map(function(a) {
        return Object.assign({}, a, { coverSrc: assetUrl(a.cover || '') });
      });
      const merged = reset ? list : (this.data.articleListItems || []).concat(list);
      this.setData({
        articleListItems: merged,
        articleListPage: page + 1,
        articleListHasMore: list.length >= 20,
        articleListLoading: false,
      });
    } catch (e) {
      this.setData({ articleListLoading: false });
    }
  },
  loadArticleFullPageMore() {
    if (this.data.articleListLoading || !this.data.articleListHasMore) return;
    this.loadArticleFullPage(false);
  },
  pickArticleCategory(e) {
    const id = parseInt(e.currentTarget.dataset.id, 10) || 0;
    if (id === this.data.articleCategoryId) return;
    this.setData({ articleCategoryId: id });
    this.loadArticleFullPage(true);
  },
  async initArticleFullPage() {
    await this.loadArticleCategories();
    await this.loadArticleFullPage(true);
  },
  onLoadArticleFull(q) {
    if (q && q.title) wx.setNavigationBarTitle({ title: decodeURIComponent(q.title) });
    if (q && q.component_id) this._queryCid = q.component_id;
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