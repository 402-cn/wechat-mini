const api = () => {
  try {
    var g = getApp().globalData.apiBase;
    if (g) return g;
  } catch (e) {}
  try {
    return require('./mp_config.js').apiBase || '';
  } catch (e2) {}
  return '';
};
function mpDevWarn(kind, cid, err) {
  try {
    if (wx.getAccountInfoSync().miniProgram.envVersion !== 'develop') return;
  } catch (e) { return; }
  var msg = err && (err.errMsg || err.message || err);
  console.warn('[mp ' + kind + ']', cid || '', msg || '');
}
function siteRoot() {
  try {
    var c = require('./mp_config.js');
    if (c.siteRoot) return c.siteRoot;
    var a = c.apiBase || '';
    if (a) {
      if (a.endsWith('/api')) return a.slice(0, -4);
      return a.replace(/\/api\/?$/, '');
    }
  } catch (e) {}
  var a = api() || '';
  if (a.endsWith('/api')) return a.slice(0, -4);
  return a.replace(/\/api\/?$/, '');
}
function assetRoot() {
  try {
    var c = require('./mp_config.js');
    if (c.assetRoot) return c.assetRoot;
    if (c.siteRoot) return c.siteRoot;
  } catch (e) {}
  try {
    var g = getApp().globalData.assetRoot || getApp().globalData.siteRoot || '';
    if (g) return g;
  } catch (e2) {}
  return siteRoot();
}
function assetUrl(url) {
  if (!url) return '';
  if (url.indexOf('http://') === 0 || url.indexOf('https://') === 0 || url.indexOf('data:') === 0) return url;
  var root = assetRoot();
  if (!root) root = siteRoot();
  var name = url.split('/').pop().split('?')[0];
  // 部署包 stock 图在 assets/images/，API/demo 逻辑路径为 /uploads/stock/
  if (url.indexOf('/uploads/stock/') === 0 || url.indexOf('/uploads/images/') === 0) {
    return root ? root + '/assets/images/' + name : url;
  }
  if (url.indexOf('/uploads/') === 0) return root ? root + url : url;
  if (url.indexOf('./assets/uploads/') === 0) return root ? root + url.slice(1) : url;
  if (url.indexOf('/assets/uploads/') === 0) return root ? root + url : url;
  if (url.indexOf('./assets/images/') === 0 || url.indexOf('/assets/images/') === 0) {
    return root ? root + '/assets/images/' + name : url;
  }
  if (url.indexOf('./assets/') === 0) return '/' + url.slice(2);
  if (url.indexOf('/assets/tab/') === 0) return url;
  if (url.indexOf('/assets/') === 0) return root ? root + url : url;
  if (url.indexOf('assets/') === 0) return '/' + url;
  return url;
}
function saveSession(j) {
  if (j && j.code === 0 && j.data && j.data.session_id) {
    wx.setStorageSync('session_id', j.data.session_id);
  }
}
function clearSession() {
  try { wx.removeStorageSync('session_id'); } catch (e) {}
}
function apiHeaders() {
  var h = { 'Content-Type': 'application/json' };
  try {
    var sid = wx.getStorageSync('session_id');
    if (sid) h['X-Session-Id'] = sid;
  } catch (e) {}
  return h;
}
function req(url, method, data) {
  return new Promise((resolve, reject) => {
    var base = api();
    if (!base) {
      mpDevWarn('config', '', 'apiBase 未配置，请检查 utils/mp_config.js');
      return reject(new Error('apiBase missing'));
    }
    wx.request({
      url: base + url,
      method: method || 'GET',
      data: data || {},
      header: apiHeaders(),
      success: (res) => resolve(res.data),
      fail: (err) => {
        mpDevWarn('request', url, err);
        reject(err);
      }
    });
  });
}
function toastMsg(j, successText, failText) {
  var m = (j && j.message) || '';
  if (m === 'ok' || m === 'OK') m = '';
  if (j && j.code === 0) return successText || m || '操作成功';
  return m || failText || '操作失败';
}
function wxLogin() {
  return new Promise((resolve, reject) => {
    wx.login({
      success: (r) => {
        if (!r.code) return reject(new Error('wx.login failed'));
        req('/auth/wx-login.php', 'POST', { code: r.code }).then(function(j) {
          saveSession(j);
          resolve(j);
        }).catch(reject);
      },
      fail: reject
    });
  });
}
function uploadAvatar(filePath) {
  return new Promise((resolve, reject) => {
    wx.uploadFile({
      url: api() + '/user/avatar_upload.php',
      filePath: filePath,
      name: 'file',
      header: apiHeaders(),
      success: (res) => {
        try { resolve(JSON.parse(res.data)); } catch (e) { reject(e); }
      },
      fail: reject
    });
  });
}
function wxLoginWithProfile() {
  return wxLogin();
}

function showAppModal(content, title) {
  return new Promise(function(resolve) {
    const pages = getCurrentPages();
    const page = pages[pages.length - 1];
    if (!page || !page.setData) {
      wx.showModal({ title: title || '提示', content: content || '', showCancel: false, confirmText: '确定', success: resolve });
      return;
    }
    page._appModalResolve = resolve;
    page.setData({ appModalShow: true, appModalTitle: title || '提示', appModalContent: content || '' });
  });
}
module.exports = { req, wxLogin, wxLoginWithProfile, uploadAvatar, api, toastMsg, showAppModal, assetUrl, assetRoot, siteRoot, saveSession, clearSession, mpDevWarn };