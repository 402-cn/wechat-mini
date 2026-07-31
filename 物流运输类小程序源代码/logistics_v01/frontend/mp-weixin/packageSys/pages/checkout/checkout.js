const { req } = require('../../../utils/api');
Page({
  data: { showMpTabBar: true, mpActiveTab: '', mpTabPrimary: "#455a64", mpTabItems: [{"icon":"/assets/tab/home.png","iconActive":"/assets/tab/home_active.png","page_key":"home","text":"首页"},{"icon":"/assets/tab/category.png","iconActive":"/assets/tab/category_active.png","page_key":"category","text":"分类"},{"icon":"/assets/tab/cart.png","iconActive":"/assets/tab/cart_active.png","page_key":"cart","text":"购物车"},{"icon":"/assets/tab/mine.png","iconActive":"/assets/tab/mine_active.png","page_key":"mine","text":"我的"}],  appModalShow: false, appModalTitle: '提示', appModalContent: '',  name: '', phone: '', addr: '', orderId: 0, fromCart: 1, wxPayEnabled: false, addresses: [], addrLabel: '' },
  onLoad(q) {
    this.setData({ orderId: parseInt(q.order_id || '0', 10), fromCart: q.from_cart === '0' ? 0 : 1 });
  },
  onShow() {
    req('/config/pay.php').then(j => {
      if (j && j.code === 0 && j.data) this.setData({ wxPayEnabled: !!j.data.wx_pay_enabled });
    }).catch(() => {});
    req('/address/list.php').then(j => {
      if (!j || j.code !== 0 || !j.data) return;
      const list = (j.data.list || []).map(function(a) {
        return Object.assign({}, a, { label: (a.is_default ? '[默认] ' : '') + a.name + ' ' + a.phone + ' ' + a.detail });
      });
      const def = list.filter(function(a){ return a.is_default; })[0] || list[0];
      const patch = { addresses: list };
      if (def) {
        patch.name = def.name || '';
        patch.phone = def.phone || '';
        patch.addr = def.detail || '';
        patch.addrLabel = def.label || '';
      }
      this.setData(patch);
    }).catch(() => {});
  },
  pickAddress(e) {
    const idx = parseInt(e.detail.value, 10);
    const a = (this.data.addresses || [])[idx];
    if (!a) return;
    this.setData({ name: a.name || '', phone: a.phone || '', addr: a.detail || '', addrLabel: a.label || '' });
  },
  async createOrder() {
    const body = { from_cart: this.data.fromCart, address_name: this.data.name, address_phone: this.data.phone, address_detail: this.data.addr };
    if (this.data.orderId > 0) return { code: 0, data: { order_id: this.data.orderId } };
    return req('/order/create.php', 'POST', body);
  },
  async payBalance() {
    const created = await this.createOrder();
    if (created.code !== 0) return wx.showToast({ title: created.message || '下单失败', icon: 'none' });
    const j = await req('/order/pay.php', 'POST', { order_id: created.data.order_id, pay_type: 'balance' });
    wx.showToast({ title: j.message || (j.code === 0 ? '支付成功' : '失败'), icon: 'none' });
    if (j.code === 0) wx.redirectTo({ url: '/packageSys/pages/order-list/order-list' });
  },
  async payWx() {
    const created = await this.createOrder();
    if (created.code !== 0) return wx.showToast({ title: created.message || '下单失败', icon: 'none' });
    const j = await req('/order/pay.php', 'POST', { order_id: created.data.order_id, pay_type: 'wechat' });
    if (j.code !== 0 || !j.data || !j.data.payment) return wx.showToast({ title: j.message || '支付失败', icon: 'none' });
    const p = j.data.payment;
    wx.requestPayment({
      timeStamp: p.timeStamp, nonceStr: p.nonceStr, package: p.package, signType: p.signType || 'MD5', paySign: p.paySign,
      success: () => wx.redirectTo({ url: '/packageSys/pages/order-list/order-list' }),
      fail: () => wx.showToast({ title: '支付取消', icon: 'none' })
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