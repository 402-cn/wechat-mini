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
  data: { appModalShow: false, appModalTitle: '提示', appModalContent: '',  siteRoot: __mpSiteRoot, assetRoot: __mpAssetRoot, loggedIn: false, nickname: '微信用户', avatar: '', points: 0, balance: 0, couponCount: 0, deposit: 0, levelName: '普通会员', benefits: [], gridNav_education_v05_mine_03: {"items":[{"icon":"/uploads/stock/education_1.jpg","iconSrc":"","navUrl":"/packageSys/pages/order-list/order-list","text":"我的订单"},{"icon":"/uploads/stock/education_2.jpg","iconSrc":"","navUrl":"/packageSys/pages/coupon-list/coupon-list","text":"优惠券"},{"icon":"/uploads/stock/education_3.jpg","iconSrc":"","navUrl":"/packageSys/pages/address-list/address-list","text":"收货地址"},{"icon":"/uploads/stock/education_4.jpg","iconSrc":"","navUrl":"/packageSub/pages/contact/contact","text":"客服中心"}]}, gridNav_education_v05_mine_04: {"items":[{"icon":"/uploads/stock/education_1.jpg","iconSrc":"","navUrl":"/packageSys/pages/member-center/member-center","text":"会员中心"},{"icon":"/uploads/stock/education_2.jpg","iconSrc":"","navUrl":"/packageSys/pages/member-center/member-center","text":"积分商城"},{"icon":"/uploads/stock/education_3.jpg","iconSrc":"","navUrl":"/packageSys/pages/invite/invite","text":"邀请好友"},{"icon":"/uploads/stock/education_4.jpg","iconSrc":"","navUrl":"/packageSys/pages/settings/settings","text":"设置"}]}, promoPair_education_v05_mine_06: {"items":[{"bgColor":"#f3e8ff","image":"/uploads/stock/education_44.jpg","imageSrc":"","navUrl":"/packageSys/pages/member-center/member-center","title":"会员专享"},{"bgColor":"#e8f8f0","image":"/uploads/stock/education_45.jpg","imageSrc":"","navUrl":"/packageSys/pages/invite/invite","title":"邀请有礼"}]} },
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
async loadCenter() {
    const { req, assetUrl } = require('../../utils/api');
    try {
      const j = await req('/user/center.php');
      if (!j || j.code !== 0) return;
      const d = j.data || {};
      const u = d.user || {};
      this.setData({
        loggedIn: !!d.logged_in,
        nickname: u.nickname || u.username || u.phone || '用户',
        avatar: assetUrl(u.avatar || ''),
        points: u.points || 0,
        balance: u.balance || 0,
        couponCount: d.coupon_count || 0,
        deposit: u.deposit || 0,
        levelName: u.member_level_name || '普通会员',
        benefits: d.benefits || []
      });
    } catch (e) {}
  },
  async doLogin() {
    const { wxLoginWithProfile, toastMsg } = require('../../utils/api');
    const j = await wxLoginWithProfile();
    if (j.code === 0) {
      await this.loadCenter();
      wx.showToast({ title: toastMsg(j, '登录成功，点击头像和昵称可完善资料', '登录失败'), icon: 'none' });
    } else wx.showToast({ title: j.message || '登录失败', icon: 'none' });
  },
  async doLogout() {
    const { req, clearSession } = require('../../utils/api');
    await req('/auth/logout.php', 'POST', {});
    clearSession();
    wx.showToast({ title: '已退出', icon: 'none' });
    await this.loadCenter();
  },
  onChooseAvatar(e) {
    const path = e.detail && e.detail.avatarUrl;
    if (!path) return;
    const { uploadAvatar, assetUrl, toastMsg } = require('../../utils/api');
    uploadAvatar(path).then((j) => {
      if (j.code !== 0) {
        wx.showToast({ title: toastMsg(j, '头像已更新', '上传失败'), icon: 'none' });
        return;
      }
      const url = assetUrl((j.data && j.data.url) || '');
      this.setData({ avatar: url });
      wx.showToast({ title: '头像已更新', icon: 'none' });
      if (typeof this.loadCenter === 'function') this.loadCenter();
    }).catch(() => wx.showToast({ title: '上传失败', icon: 'none' }));
  },
  async onNickBlur(e) {
    const nick = ((e.detail && e.detail.value) || '').trim();
    if (!nick || nick === this.data.nickname) return;
    const { req, toastMsg } = require('../../utils/api');
    const j = await req('/user/profile.php', 'POST', { nickname: nick });
    if (j.code === 0) {
      this.setData({ nickname: nick });
      wx.showToast({ title: '昵称已保存', icon: 'none' });
    } else {
      wx.showToast({ title: toastMsg(j, '已保存', '保存失败'), icon: 'none' });
    }
  },
  async openVip() {
    const { req } = require('../../utils/api');
    const j = await req('/user/vip_open.php', 'POST', { level_id: 6 });
    wx.showToast({ title: j.message || (j.code === 0 ? '开通成功' : '失败'), icon: 'none' });
    if (j.code === 0) this.loadCenter();
  },
  goOrders(e) {
    const status = e.currentTarget.dataset.status || '';
    try {
      const app = getApp();
      if (app && app.globalData) app.globalData.orderListStatus = status;
    } catch (err) {}
    const tabUrl = '/pages/order/order';
    wx.switchTab({
      url: tabUrl,
      fail: () => {
        wx.navigateTo({ url: '/packageSys/pages/order-list/order-list' + (status ? '?status=' + encodeURIComponent(status) : '') });
      }
    });
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
onShow() {
    if (this.loadCenter) this.loadCenter().catch(function(){});
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