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
  data: { appModalShow: false, appModalTitle: '提示', appModalContent: '',  siteRoot: __mpSiteRoot, assetRoot: __mpAssetRoot, notice_auto_v06_contact_02: {"bgColor":"#f0f9ff","content":"客服热线：400-000-0000（示例）  工作时间：9:00-21:00","duration":"6.29s","fontSize":26,"prefixTitle":"","scrollDirection":"left","scrollSpeed":35,"showIcon":true,"textColor":"#333","trackClass":"to-left"}, noticeWidgets: [{"key":"notice_auto_v06_contact_02","cid":"auto_v06_contact_02"}], showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#9b59b6", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}] },
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
    await this._mpLoadNotice_notice_auto_v06_contact_02()
  },
  async _mpLoadNotice_notice_auto_v06_contact_02() {
    const { req } = require('../../../utils/api');
    try {
      var j = await req('/notice/get.php?id=' + encodeURIComponent("auto_v06_contact_02"));
      if (!j || j.code !== 0 || !j.data || !j.data.content) return;
      var d = j.data;
      var speed = d.scrollSpeed || 50;
      var dir = d.scrollDirection || 'left';
      this.setData({
        notice_auto_v06_contact_02: {
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
    } catch (e) { this.mpDevWarn('notice', "auto_v06_contact_02", e); }
  },
mpDevWarn(kind, cid, err) {
    const { mpDevWarn } = require('../../../utils/api');
    mpDevWarn(kind, cid, err);
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
    if (this.loadNotices) this.loadNotices().catch(function(){});
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