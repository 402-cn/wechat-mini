const app = document.getElementById('app');
const allPages = window.PAGES || [];
const globalConfig = window.GLOBAL_CONFIG || {};
const apiBase = (window.APP_CONFIG && window.APP_CONFIG.apiBaseUrl) || './api';
function assetUrl(u) {
  if (!u) return u;
  if (/^(https?:)?\/\//i.test(u) || u.indexOf('data:') === 0) return u;
  var s = String(u);
  s = s.replace(/([?&])v=[^&#]*(?=&|$)/g, '$1').replace(/\?&/g, '?').replace(/[?&]$/, '');
  var um = s.match(/\/uploads\/(?:stock|images)\/([^?#]+)/i);
  if (um) s = './assets/images/' + um[1];
  else if (s.indexOf('/uploads/') === 0) s = './assets/images/' + s.split('/').pop().split('?')[0];
  var v = (window.APP_CONFIG && window.APP_CONFIG.assetVersion) || '';
  if (!v) return s;
  return s + (s.indexOf('?') >= 0 ? '&' : '?') + 'v=' + encodeURIComponent(v);
}
const guideCapture = !!window.GUIDE_CAPTURE;
function showH5Toast(msg, ms) {
  var el = document.getElementById('h5-toast');
  if (!el) { el = document.createElement('div'); el.id = 'h5-toast'; el.className = 'h5-toast'; document.body.appendChild(el); }
  el.textContent = msg || '';
  el.classList.add('show');
  clearTimeout(el._hideTimer);
  el._hideTimer = setTimeout(function(){ el.classList.remove('show'); }, ms || 2000);
}
function instAttr(cid) { return cid ? ' data-instance-id="' + cid + '"' : ''; }
function parseHashPath(raw) {
  raw = (raw || 'home').replace(/^#/, '');
  var qIdx = raw.indexOf('?');
  var path = (qIdx >= 0 ? raw.slice(0, qIdx) : raw) || 'home';
  var query = {};
  if (qIdx >= 0) {
    raw.slice(qIdx + 1).split('&').forEach(function (pair) {
      var kv = pair.split('=');
      if (kv[0]) query[decodeURIComponent(kv[0])] = decodeURIComponent(kv[1] || '');
    });
  }
  return { path: path, query: query };
}
function parseRoute() {
  return parseHashPath((location.hash || '#home').replace('#', ''));
}
function navTo(hashPath) {
  hashPath = hashPath || 'home';
  location.hash = hashPath;
  var r = parseHashPath(hashPath);
  renderApp(r.path, r.query);
}
function getHomePageKey() {
  var tabBar = globalConfig.tabBar;
  if (tabBar && tabBar.enabled && tabBar.items && tabBar.items.length) {
    return tabBar.items[0].page_key || 'home';
  }
  return (allPages[0] && allPages[0].page_key) || 'home';
}
function goBack() {
  if (window.history.length > 1) history.back();
  else navTo(getHomePageKey());
}
function isTabBarPage(path) {
  var tabBar = globalConfig.tabBar;
  if (!tabBar || !tabBar.enabled || !tabBar.items) return false;
  return tabBar.items.some(function(it){ return it.page_key === path; });
}
function isSubPage(path) {
  return path === 'search-article' || path === 'search-product' || path === 'article-list' || path === 'product-list' || path === 'article-detail' || path === 'product-detail' || path === 'cart' || path === 'checkout' || path === 'order' || path === 'order-list' || path === 'order-detail' || path === 'address-list' || path === 'settings' || path === 'invite' || path === 'coupon-list' || path === 'member-center' || path === 'login' || path === 'register' || path === 'forgot-password' || path === 'group-buy' || path === 'flash-sale' || path === 'live-room' || path === 'check-in' || path === 'favorites-list' || path === 'footprint-list' || path === 'hobbies-list' || path === 'wallet-recharge' || path === 'wallet-logs' || path === 'points-logs';
}
function renderPageComponents(page) {
  var comps = pageComponents(page).filter(function(c){ return c.visible !== false && c.type !== 'orderStatusAnchor'; });
  var html = [];
  for (var i = 0; i < comps.length; i++) {
    var comp = comps[i];
    var block = renderComponent(comp);
    var next = comps[i + 1];
    var prev = comps[i - 1];
    if (comp.type === 'pageHeader' && next && next.type === 'user') {
      block = block.replace('class="component page-header"', 'class="component page-header mine-head-top"');
    }
    if (comp.type === 'user' && prev && prev.type === 'pageHeader') {
      block = block.replace('class="component user-center"', 'class="component user-center mine-head-user"');
    }
    html.push(block);
  }
  return html.join('');
}
function renderSubPageNav(title) {
  return '<div class="sub-page-nav"><button type="button" class="sub-back" onclick="goBack()">‹ 返回</button><span class="sub-title">' + (title || '') + '</span></div>';
}
function openArticle(id) { navTo('article-detail?id=' + encodeURIComponent(id)); }
function openProduct(id) { navTo('product-detail?id=' + encodeURIComponent(id)); }
function applyTheme() {
  var theme = globalConfig.theme || {};
  var pb = globalConfig.pageBackground || {};
  if (theme.primaryColor) document.documentElement.style.setProperty('--primary-color', theme.primaryColor);
  if (pb.type === 'custom') {
    if (pb.color) app.style.background = pb.color;
    if (pb.image) { app.style.backgroundImage = 'url(' + pb.image + ')'; app.style.backgroundSize = 'cover'; app.style.backgroundPosition = 'center top'; }
    else app.style.backgroundImage = '';
  } else if (theme.backgroundColor) {
    app.style.background = theme.backgroundColor;
    app.style.backgroundImage = '';
  }
}
if (!guideCapture) {
  fetch(apiBase + '/site/config.php').then(function(r){ return r.json(); }).then(function(j){
    if (j.code === 0 && j.data) { Object.assign(globalConfig, j.data); applyTheme(); renderApp(parseRoute().path); }
  }).catch(function(){});
}
function normalizeLinkObj(link) {
  if (!link) return { type: 'none' };
  if (typeof link === 'string') {
    if (!link) return { type: 'none' };
    if (link.indexOf('http') === 0) return { type: 'external', url: link };
    return { type: 'internal', module: 'page', pageKey: link.replace('#','').split('?')[0] };
  }
  if (link.type === 'none' || link.linkType === 'none') return { type: 'none' };
  return link;
}
function buildSystemHash(route, params) {
  params = params || {};
  var parts = [];
  for (var k in params) { if (params[k]) parts.push(k + '=' + encodeURIComponent(params[k])); }
  return '#' + route + (parts.length ? '?' + parts.join('&') : '');
}
function resolveLinkHref(link) {
  link = normalizeLinkObj(link);
  if (link.type === 'external' && link.url) return link.url;
  if (link.type === 'internal') {
    if (link.module === 'productCategory' && link.categoryId) return '#product-list?category_id=' + encodeURIComponent(link.categoryId);
    if (link.module === 'articleCategory' && link.categoryId) return '#article-list?category_id=' + encodeURIComponent(link.categoryId);
    if (link.module === 'page' && link.pageKey) {
      if (link.pageKey === 'order') return '#order';
      if (link.pageKey === 'coupon') return '#coupon-list';
      if (link.pageKey === 'member') return '#member-center';
      if (link.pageKey === 'article') return '#search-article';
      if (link.pageKey === 'about') return '#article-list';
      return '#' + link.pageKey;
    }
    if (link.module === 'system' && link.systemRoute) return buildSystemHash(link.systemRoute, link.systemParams || {});
    if (link.module === 'article') return link.articleId ? '#article-detail?id=' + link.articleId : '#search-article';
    if (link.module === 'product') return link.productId ? '#product-detail?id=' + link.productId : '#search-product';
  }
  return '';
}
function navByHref(href, e) {
  if (!href) return;
  if (href.indexOf('#') === 0) {
    if (e) e.preventDefault();
    navTo(href.replace('#',''));
    return;
  }
}
function showImagePreview(src) {
  var o = document.createElement('div');
  o.className = 'img-preview-overlay';
  o.innerHTML = '<div class="img-preview-box"><img src="' + src + '"><br><button type="button">关闭</button></div>';
  o.querySelector('button').onclick = function(){ o.remove(); };
  o.onclick = function(ev){ if (ev.target === o) o.remove(); };
  document.body.appendChild(o);
}
function buildImageHtml(props) {
  if (!props.src) return '';
  var src = assetUrl(props.src);
  var w = props.width || '100%';
  var h = props.height || 'auto';
  var style = 'width:' + w + ';height:' + h + ';display:block;max-width:100%;object-fit:cover';
  var img = '<img src="' + src + '" style="' + style + '">';
  var action = props.clickAction || 'none';
  if (action === 'preview') return '<div class="img-click" style="cursor:pointer" data-preview-src="' + src.replace(/"/g,'&quot;') + '" onclick="showImagePreview(this.getAttribute(\'data-preview-src\'))">' + img + '</div>';
  if (action === 'link') {
    var href = resolveLinkHref(props.link);
    if (href.indexOf('#') === 0) return '<a href="' + href + '" onclick="navByHref(\'' + href + '\', event)">' + img + '</a>';
    if (href) return '<a href="' + href + '" target="_blank" rel="noopener">' + img + '</a>';
  }
  return img;
}
function renderImage(props, cid) {
  if (!props.src) return '';
  var boxId = wgBoxId('img', cid);
  wgMount(boxId, cid, props, function(el, p) { el.innerHTML = buildImageHtml(p); });
  return '<div class="component" id="' + boxId + '"' + instAttr(cid) + '>' + buildImageHtml(props) + '</div>';
}
function renderButton(props) {
  var text = props.text || '按钮';
  var bg = props.bgColor || '#2ecc71';
  var color = props.textColor || '#fff';
  var fs = (props.fontSize || 14) + 'px';
  var br = props.shape === 'pill' ? '999px' : (props.shape === 'square' ? '0' : ((props.borderRadius || 8) + 'px'));
  var width = props.width || '100%';
  var style = 'background:' + bg + ';color:' + color + ';font-size:' + fs + ';border-radius:' + br + ';width:' + width;
  if (props.gradient) style = 'background:linear-gradient(90deg,' + bg + ',' + (props.gradientEnd||'#27ae60') + ');color:' + color + ';font-size:' + fs + ';border-radius:' + br + ';width:' + width;
  var href = resolveLinkHref(props.link);
  if (href.indexOf('#') === 0) return '<div class="component btn-component"><a class="btn-inner" href="' + href + '" style="' + style + '" onclick="navByHref(\'' + href + '\', event)">' + text + '</a></div>';
  if (href) return '<div class="component btn-component"><a class="btn-inner" href="' + href + '" style="' + style + '" target="_blank" rel="noopener">' + text + '</a></div>';
  return '<div class="component btn-component"><span class="btn-inner" style="' + style + '">' + text + '</span></div>';
}
function renderComponent(comp) {
  var props = comp.props || {};
  var cid = comp.id || '';
  switch (comp.type) {
    case 'titleBar': return renderTitleBarUpgraded(props, cid);
    case 'noticeBar': return renderNoticeBar(props, cid);
    case 'image': return renderImage(props, cid);
    case 'button': return renderButton(props);
    case 'richText': return renderRichText(props, cid);
    case 'swiper': return renderSwiper(props, cid);
    case 'form': return renderForm(props, cid);
    case 'product': return renderProduct(props, cid);
    case 'article': return renderArticle(props, cid);
    case 'user': return renderUserCenter(props, cid);
    case 'userVip': return renderUserVip(props, cid);
    case 'userBenefits': return renderUserBenefits(props, cid);
    case 'userOrders': return renderUserOrders(props, cid);
    case 'userCommunity': return renderUserCommunity(props, cid);
    case 'searchBar': return renderSearchBarUpgraded(props, cid);
    case 'gridNav': return renderGridNav(props, cid);
    case 'pageHeader': return renderPageHeader(props, cid);
    case 'promoPair': return renderPromoPair(props, cid);
    case 'productScroll': return renderProductScroll(props, cid);
    case 'promoBanner': return renderPromoBanner(props, cid);
    case 'container': return renderContainer(props, cid);
    case 'tabNav': return renderTabNav(props, cid);
    case 'filterBar': return renderFilterBar(props, cid);
    case 'promoGrid': return renderPromoGrid(props, cid);
    case 'video': return renderVideo(props, cid);
    case 'serviceCard': return renderServiceCard(props, cid);
    case 'listMenu': return renderListMenu(props, cid);
    case 'statsRow': return renderStatsRow(props, cid);
    case 'walletCard': return renderWalletCard(props, cid);
    case 'floatingButton': return renderFloatingButton(props, cid);
    case 'waterfall': return renderWaterfall(props, cid);
    case 'featureCard': return renderFeatureCard(props, cid);
    case 'loginBanner': return renderLoginBanner(props, cid);
    case 'rate': return renderRate(props, cid);
    case 'serviceFloat': return renderServiceFloat(props, cid);
    case 'locationPicker': return renderLocationPicker(props, cid);
    case 'groupBuy': return renderMarketingEntry(props, cid, 'groupBuy');
    case 'flashSale': return renderMarketingEntry(props, cid, 'flashSale');
    case 'liveEntry': return renderMarketingEntry(props, cid, 'liveEntry');
    case 'checkIn': return renderCheckIn(props, cid);
    case 'messageBoard': return renderMessageBoard(props, cid);
    case 'quiz': return renderQuiz(props, cid);
    case 'checkinActivity': return renderCheckinActivity(props, cid);
    case 'map': return renderMap(props, cid);
    default: return '<div class="component" style="padding:12px;color:#999">[' + comp.type + '] 组件预览</div>';
  }
}
function renderNoticeBar(props, cid) {
  var boxId = 'nb-' + (cid || Math.random().toString(36).slice(2));
  setTimeout(function(){ loadNoticeBar(boxId, cid, props); }, guideCapture ? 0 : 30);
  return '<div class="component notice-bar" id="' + boxId + '"' + instAttr(cid) + ' style="color:#999;padding:12px">加载公告...</div>';
}
function loadNoticeBar(boxId, cid, fallback) {
  if (guideCapture) { paintNoticeBar(boxId, fallback); return; }
  if (!cid) { paintNoticeBar(boxId, fallback); return; }
  fetch(apiBase + '/notice/get.php?id=' + encodeURIComponent(cid)).then(function(r){ return r.json(); }).then(function(j){
    if (j && j.code === 0 && j.data) paintNoticeBar(boxId, j.data); else paintNoticeBar(boxId, fallback);
  }).catch(function(){ paintNoticeBar(boxId, fallback); });
}
function paintNoticeBar(boxId, props) {
  props = props || {};
  var text = props.content || '';
  var speed = props.scrollSpeed || 50;
  if (speed < 10) speed = 10;
  if (speed > 200) speed = 200;
  var dur = (220 / speed) + 's';
  var dir = props.scrollDirection === 'right' ? 'to-right' : 'to-left';
  var fs = Math.round((props.fontSize || 28) / 2);
  var el = document.getElementById(boxId);
  if (!el) return;
  el.style.color = props.textColor || '#333';
  el.style.background = props.bgColor || '#fff';
  el.style.fontSize = fs + 'px';
  el.innerHTML = (props.prefixTitle?'<span class="notice-prefix">'+props.prefixTitle+'</span>':'') + (props.showIcon!==false?'<span class="notice-icon">📢</span>':'') + '<div class="notice-viewport"><div class="notice-track ' + dir + '" style="animation-duration:' + dur + '"><span class="notice-text">' + text + '</span><span class="notice-text">' + text + '</span></div></div>';
}
function renderSwiper(props, cid) {
  var boxId = 'sw-' + (cid || Math.random().toString(36).slice(2));
  setTimeout(function(){ loadSwiper(boxId, cid, props); }, guideCapture ? 0 : 30);
  var h = Math.round((props.height || 360) / 2);
  return '<div class="component swiper-box" id="' + boxId + '"' + instAttr(cid) + ' style="height:' + h + 'px"><div class="swiper-track"></div><div class="swiper-dots"></div></div>';
}
function loadSwiper(boxId, cid, fallback) {
  if (guideCapture) { paintSwiper(boxId, {height:fallback.height,autoplay:fallback.autoplay,interval:fallback.interval,items:fallback.items||[]}); return; }
  if (!cid) { paintSwiper(boxId, fallback); return; }
  fetch(apiBase + '/swiper/list.php?id=' + encodeURIComponent(cid)).then(function(r){ return r.json(); }).then(function(j){
    if (j && j.code === 0 && j.data) paintSwiper(boxId, j.data); else paintSwiper(boxId, fallback);
  }).catch(function(){ paintSwiper(boxId, fallback); });
}
function paintSwiper(boxId, data) {
  data = data || {};
  var box = document.getElementById(boxId);
  if (!box) return;
  var h = Math.round((data.height || 360) / 2);
  box.style.height = h + 'px';
  box.dataset.autoplay = data.autoplay ? '1' : '0';
  box.dataset.interval = data.interval || 3000;
  var items = data.items || [];
  var slides = items.map(function(item, idx) {
    var img = item.image || item.url || '';
    var href = resolveLinkHref(item.link);
    var imgTag = img ? '<img src="' + img + '" alt="">' : '<div style="height:100%;display:flex;align-items:center;justify-content:center;color:#999">轮播图' + (idx+1) + '</div>';
    var inner = imgTag;
    if (href.indexOf('#') === 0) inner = '<a href="' + href + '" style="display:block;width:100%;height:100%" onclick="navByHref(\'' + href + '\', event)">' + imgTag + '</a>';
    else if (href) inner = '<a href="' + href + '" style="display:block;width:100%;height:100%" target="_blank" rel="noopener">' + imgTag + '</a>';
    return '<div class="swiper-slide">' + inner + '</div>';
  }).join('');
  var dots = items.map(function(_, i){ return '<span class="swiper-dot' + (i===0?' active':'') + '" data-idx="' + i + '"></span>'; }).join('');
  var track = box.querySelector('.swiper-track');
  var dotsEl = box.querySelector('.swiper-dots');
  if (track) track.innerHTML = slides;
  if (dotsEl) dotsEl.innerHTML = dots;
  initSwipers();
}
function initSwipers() {
  document.querySelectorAll('.swiper-box').forEach(function(box) {
    var track = box.querySelector('.swiper-track');
    var dots = box.querySelectorAll('.swiper-dot');
    var total = dots.length;
    if (!total) return;
    var idx = 0;
    function go(n) {
      idx = n;
      track.style.transform = 'translateX(-' + (n * 100) + '%)';
      dots.forEach(function(d, i){ d.classList.toggle('active', i === n); });
    }
    dots.forEach(function(d){ d.addEventListener('click', function(){ go(parseInt(d.dataset.idx, 10)); }); });
    if (box.dataset.autoplay === '1' && total > 1) {
      setInterval(function(){ go((idx + 1) % total); }, parseInt(box.dataset.interval, 10) || 3000);
    }
  });
}
function imgThumbUrl(url) {
  if (!url) return url;
  if (url.indexOf('http') === 0 || url.indexOf('data:') === 0) return url;
  var base = String(url).split('?')[0];
  if (/\.svg$/i.test(base) || base.indexOf('/demo/') >= 0) return base;
  if (base.indexOf('assets/images/') >= 0 || base.indexOf('/uploads/stock/') >= 0) {
    var um = base.match(/\/uploads\/(?:stock|images)\/([^?#]+)/i);
    if (um) return './assets/images/' + um[1];
    if (base.indexOf('./assets/images/') === 0) return base;
    if (base.indexOf('assets/images/') === 0) return './' + base;
    return base;
  }
  if (base.indexOf('assets/uploads/') >= 0) return String(url).split('?')[0];
  var dot = base.lastIndexOf('.');
  if (dot < 0) return base;
  return base.slice(0, dot) + '_thumb' + base.slice(dot);
}
function productImg(p, useThumb) {
  var img = (p && (p.image || p.product_image)) ? String(p.image || p.product_image) : '';
  var demoFiles = ['apple.jpg','banana.jpg','lychee.jpg','orange.jpg','grapes.jpg','strawberry.jpg','tomato.jpg','cucumber.jpg','lettuce.jpg','carrot.jpg','broccoli.jpg','corn.jpg','chicken.jpg','beef.jpg','egg.jpg','pork.jpg','roastduck.jpg','lamb.jpg','milk.jpg','bread.jpg','cheesecake.jpg','cheese.jpg','yogurt.jpg','cookies.jpg','nuts.jpg','chips.jpg','chocolate.jpg','mango.jpg','candy.jpg','beefjerky.jpg'];
  if (!img || img.indexOf('data:') === 0) {
    var pid = parseInt(p && (p.product_id || p.id), 10) || 1;
    if (pid < 1) pid = 1; if (pid > 30) pid = ((pid - 1) % 30) + 1;
    img = './assets/images/' + demoFiles[pid - 1];
  }
  if (useThumb !== false) {
    var base = img.split('/').pop() || '';
    if (demoFiles.indexOf(base) < 0) img = imgThumbUrl(img);
  }
  return assetUrl(img);
}
var _infiniteObservers = {};
function detachInfiniteScroll(key) {
  var st = _infiniteObservers[key];
  if (st && st.io) st.io.disconnect();
  delete _infiniteObservers[key];
}
function setInfiniteFooter(key, text) {
  var st = _infiniteObservers[key];
  if (st && st.footer) st.footer.textContent = text || '';
}
function attachInfiniteScroll(key, container, onLoadMore) {
  detachInfiniteScroll(key);
  var sentinel = container.querySelector('.infinite-sentinel');
  if (!sentinel) { sentinel = document.createElement('div'); sentinel.className = 'infinite-sentinel'; container.appendChild(sentinel); }
  var footer = container.querySelector('.infinite-footer');
  if (!footer) { footer = document.createElement('div'); footer.className = 'infinite-footer'; container.appendChild(footer); }
  var io = new IntersectionObserver(function(entries) { if (entries[0] && entries[0].isIntersecting) onLoadMore(); }, { root: null, rootMargin: '120px' });
  io.observe(sentinel);
  _infiniteObservers[key] = { io: io, footer: footer };
}
function createPagedLoader(key, opts) {
  var state = { page: 1, loading: false, hasMore: true };
  var load;
  load = function(reset) {
    if (state.loading) return;
    if (!reset && !state.hasMore) return;
    if (reset) { state.page = 1; state.hasMore = true; opts.listEl.innerHTML = '<div class="article-empty">加载中...</div>'; detachInfiniteScroll(key); }
    state.loading = true;
    setInfiniteFooter(key, '加载中...');
    fetch(opts.buildUrl(state.page)).then(function(r){ return r.json(); }).then(function(json) {
      var data = (json && json.code === 0 && json.data) ? json.data : {};
      var list = data.list || [];
      if (reset) opts.listEl.innerHTML = '';
      if (!list.length && state.page === 1) {
        opts.listEl.innerHTML = '<div class="article-empty">' + (opts.emptyText || '暂无数据') + '</div>';
        state.loading = false; state.hasMore = false; detachInfiniteScroll(key); return;
      }
      opts.appendItems(list);
      state.hasMore = !!data.has_more;
      state.page += 1;
      state.loading = false;
      if (state.hasMore) {
        setInfiniteFooter(key, '');
        attachInfiniteScroll(key, opts.scrollRoot || opts.listEl, function(){ load(false); });
      } else {
        setInfiniteFooter(key, list.length ? '没有更多了' : '');
        detachInfiniteScroll(key);
      }
    }).catch(function() {
      state.loading = false;
      if (state.page === 1) opts.listEl.innerHTML = '<div class="article-empty">加载失败</div>';
      else setInfiniteFooter(key, '加载失败，上滑重试');
    });
  };
  return load;
}
function headerSearchSubmit(keyword, type) {
  keyword = (keyword || '').trim();
  if (type !== 'article' && type !== 'product') return;
  var base = type === 'product' ? 'search-product' : 'search-article';
  navTo(base + (keyword ? '?q=' + encodeURIComponent(keyword) : ''));
}
function loadSearchArticlePage(keyword) {
  var listEl = document.getElementById('search-article-list'); if (!listEl) return;
  var load = createPagedLoader('search-article', {
    listEl: listEl,
    scrollRoot: listEl.parentElement || listEl,
    emptyText: '无结果',
    buildUrl: function(page) {
      var url = apiBase + '/article/list.php?page=' + page + '&page_size=20';
      if (keyword) url += '&keyword=' + encodeURIComponent(keyword);
      return url;
    },
    appendItems: function(list) {
      listEl.insertAdjacentHTML('beforeend', list.map(function(a){ return renderArticleItem(a, 'image-text', true); }).join(''));
    }
  });
  load(true);
}
function bindSearchArticleInput() {
  var input = document.getElementById('search-article-input'); if (!input) return;
  input.addEventListener('keydown', function(e){
    if (e.key === 'Enter') {
      var kw = input.value.trim();
      navTo('search-article' + (kw ? '?q=' + encodeURIComponent(kw) : ''));
    }
  });
}
function renderSearchArticlePage(query) {
  var kw = (query.q || '').trim();
  setTimeout(function(){ bindSearchArticleInput(); loadSearchArticlePage(kw); }, 0);
  return renderSubPageNav('搜索文章') + '<div class="search-page"><div class="search-page-input"><input type="search" id="search-article-input" placeholder="搜索标题或摘要" value="' + kw.replace(/"/g,'&quot;') + '"></div><div class="search-page-list article-box" id="search-article-list"><div class="article-empty">加载中...</div></div></div>';
}
function loadSearchProductPage(keyword) {
  var listEl = document.getElementById('search-product-list'); if (!listEl) return;
  var load = createPagedLoader('search-product', {
    listEl: listEl,
    scrollRoot: listEl.parentElement || listEl,
    emptyText: '无结果',
    buildUrl: function(page) {
      var url = apiBase + '/product/list.php?page=' + page + '&page_size=20';
      if (keyword) url += '&keyword=' + encodeURIComponent(keyword);
      return url;
    },
    appendItems: function(list) {
      listEl.insertAdjacentHTML('beforeend', list.map(function(p){
        return '<div class="product-search-row" onclick="openProduct(' + p.id + ')"><img src="' + productImg(p) + '" alt=""><div><div class="product-name">' + (p.name||'') + '</div><div class="product-price">¥' + (p.price||0) + '</div></div></div>';
      }).join(''));
    }
  });
  load(true);
}
function bindSearchProductInput() {
  var input = document.getElementById('search-product-input'); if (!input) return;
  input.addEventListener('keydown', function(e){
    if (e.key === 'Enter') {
      var kw = input.value.trim();
      navTo('search-product' + (kw ? '?q=' + encodeURIComponent(kw) : ''));
    }
  });
}
function renderSearchProductPage(query) {
  var kw = (query.q || '').trim();
  setTimeout(function(){ bindSearchProductInput(); loadSearchProductPage(kw); }, 0);
  return renderSubPageNav('搜索商品') + '<div class="search-page"><div class="search-page-input"><input type="search" id="search-product-input" placeholder="搜索商品名称" value="' + kw.replace(/"/g,'&quot;') + '"></div><div class="search-page-list" id="search-product-list"><div class="article-empty">加载中...</div></div></div>';
}
function isDemoPlaceholderCover(cover, id) {
  if (String(id).indexOf('demo_') === 0) return true;
  if (!cover) return false;
  return /\/demo\/a\d+\.svg/i.test(cover) || String(cover).indexOf('demo/a') >= 0;
}
function articleDetailCoverHtml(a) {
  if (!a || !a.cover || isDemoPlaceholderCover(a.cover, a.id)) return '';
  var url = articleCoverUrl(a.cover, a.id);
  if (!url) return '';
  return '<img class="detail-cover" src="' + url + '" alt="">';
}
function ensureVisitorKey() {
  var vk = localStorage.getItem('visitor_key');
  if (!vk) { vk = 'v_' + Date.now(); localStorage.setItem('visitor_key', vk); }
  return vk;
}
function recordFootprint(targetType, targetId) {
  var id = parseInt(targetId, 10) || 0;
  if (!id) return;
  fetch(apiBase + '/footprint/record.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ target_type: targetType, target_id: id, visitor_key: ensureVisitorKey() }) }).catch(function(){});
}
function favoriteBtnHtml(targetType, targetId) {
  return '<button type="button" class="detail-fav-btn" data-type="' + targetType + '" data-id="' + targetId + '" onclick="toggleFavorite(this)">♡ 收藏</button>';
}
function refreshFavoriteBtn(btn) {
  if (!btn) return;
  var type = btn.getAttribute('data-type');
  var id = parseInt(btn.getAttribute('data-id'), 10) || 0;
  fetch(apiBase + '/favorite/list.php?target_type=' + encodeURIComponent(type) + '&visitor_key=' + encodeURIComponent(ensureVisitorKey())).then(function(r){ return r.json(); }).then(function(j){
    if (!j || j.code !== 0 || !j.data) return;
    var list = j.data.list || [];
    var hit = list.some(function(it){ return parseInt(it.id, 10) === id; });
    btn.textContent = hit ? '♥ 已收藏' : '♡ 收藏';
    btn.classList.toggle('favorited', hit);
  }).catch(function(){});
}
function toggleFavorite(btn) {
  var type = btn.getAttribute('data-type');
  var id = parseInt(btn.getAttribute('data-id'), 10) || 0;
  if (!id) return;
  fetch(apiBase + '/favorite/toggle.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ target_type: type, target_id: id, visitor_key: ensureVisitorKey() }) }).then(function(r){ return r.json(); }).then(function(j){
    if (!j || j.code !== 0) { showH5Toast((j && j.message) || '操作失败'); return; }
    var fav = !!(j.data && j.data.favorited);
    btn.textContent = fav ? '♥ 已收藏' : '♡ 收藏';
    btn.classList.toggle('favorited', fav);
  }).catch(function(){ showH5Toast('网络错误'); });
}
function loadInteractionListPage(elId, apiPath, targetType, openFn) {
  var el = document.getElementById(elId); if (!el) return;
  fetch(apiPath + '?target_type=' + encodeURIComponent(targetType) + '&visitor_key=' + encodeURIComponent(ensureVisitorKey())).then(function(r){ return r.json(); }).then(function(j){
    if (!j || j.code !== 0) { el.innerHTML = '<div class="article-empty">' + ((j && j.message) || '加载失败') + '</div>'; return; }
    var list = (j.data && j.data.list) || [];
    if (!list.length) { el.innerHTML = '<div class="article-empty">暂无记录</div>'; return; }
    el.innerHTML = list.map(function(it){
      var title = it.title || it.name || ('#' + it.id);
      var sub = targetType === 'product' ? ('¥' + (it.price || 0)) : (it.summary || '');
      var img = targetType === 'product' ? productImg(it, true) : articleCoverUrl(it.cover, it.id);
      var thumb = img ? '<img src="' + img + '" alt="">' : '';
      return '<div class="interaction-row" onclick="' + openFn + '(' + it.id + ')">' + thumb + '<div><strong>' + title + '</strong><p>' + sub + '</p></div></div>';
    }).join('');
  }).catch(function(){ el.innerHTML = '<div class="article-empty">加载失败</div>'; });
}
function renderFavoritesListPage() {
  setTimeout(function(){ loadMixedInteractionPage('favorites-list-page', apiBase + '/favorite/list.php'); }, 0);
  return renderSubPageNav('我的收藏') + '<div class="virtual-page" id="favorites-list-page"><div class="interaction-tabs"><button type="button" class="active" data-type="product" onclick="switchInteractionTab(this,\'favorites-list-page\',\'/favorite/list.php\')">商品</button><button type="button" data-type="article" onclick="switchInteractionTab(this,\'favorites-list-page\',\'/favorite/list.php\')">文章</button></div><div class="interaction-body"><div class="article-empty">加载中...</div></div></div>';
}
function renderFootprintListPage() {
  setTimeout(function(){ loadMixedInteractionPage('footprint-list-page', apiBase + '/footprint/list.php'); }, 0);
  return renderSubPageNav('我的足迹') + '<div class="virtual-page" id="footprint-list-page"><div class="interaction-tabs"><button type="button" class="active" data-type="product" onclick="switchInteractionTab(this,\'footprint-list-page\',\'/footprint/list.php\')">商品</button><button type="button" data-type="article" onclick="switchInteractionTab(this,\'footprint-list-page\',\'/footprint/list.php\')">文章</button></div><div class="interaction-body"><div class="article-empty">加载中...</div></div></div>';
}
function renderHobbiesListPage() {
  setTimeout(function(){ loadMixedInteractionPage('hobbies-list-page', apiBase + '/hobbies/list.php', true); }, 0);
  return renderSubPageNav('我的爱好') + '<div class="virtual-page" id="hobbies-list-page"><div class="interaction-tabs"><button type="button" class="active" data-type="product" onclick="switchInteractionTab(this,\'hobbies-list-page\',\'/hobbies/list.php\',true)">商品</button><button type="button" data-type="article" onclick="switchInteractionTab(this,\'hobbies-list-page\',\'/hobbies/list.php\',true)">文章</button></div><div class="interaction-body"><div class="article-empty">加载中...</div></div></div>';
}
function switchInteractionTab(btn, pageId, apiPath, isHobby) {
  var root = document.getElementById(pageId); if (!root) return;
  root.querySelectorAll('.interaction-tabs button').forEach(function(b){ b.classList.toggle('active', b === btn); });
  loadMixedInteractionPage(pageId, apiBase + apiPath, !!isHobby, btn.getAttribute('data-type'));
}
function loadMixedInteractionPage(pageId, apiPath, isHobby, targetType) {
  var root = document.getElementById(pageId); if (!root) return;
  var body = root.querySelector('.interaction-body'); if (!body) body = root;
  var activeBtn = root.querySelector('.interaction-tabs button.active');
  var type = targetType || (activeBtn && activeBtn.getAttribute('data-type')) || 'product';
  body.innerHTML = '<div class="article-empty">加载中...</div>';
  fetch(apiPath + '?target_type=' + encodeURIComponent(type) + '&visitor_key=' + encodeURIComponent(ensureVisitorKey()), { credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(j){
    if (!j || j.code !== 0) { body.innerHTML = '<div class="article-empty">' + ((j && j.message) || '加载失败') + '</div>'; return; }
    var list = (j.data && j.data.list) || [];
    if (!list.length) { body.innerHTML = '<div class="article-empty">暂无记录</div>'; return; }
    body.innerHTML = list.map(function(it){
      var tt = it.target_type || type;
      var title = it.title || it.name || ('#' + it.id);
      var sub = tt === 'product' ? ('¥' + (it.price || 0)) : (it.summary || '');
      if (isHobby) sub = '★★★★★ ' + sub;
      var img = tt === 'product' ? productImg(it, true) : articleCoverUrl(it.cover, it.id);
      var thumb = img ? '<img src="' + img + '" alt="">' : '';
      var openFn = tt === 'product' ? 'openProduct' : 'openArticle';
      return '<div class="interaction-row" onclick="' + openFn + '(' + it.id + ')">' + thumb + '<div><strong>' + title + '</strong><p>' + sub + '</p></div></div>';
    }).join('');
  }).catch(function(){ body.innerHTML = '<div class="article-empty">加载失败</div>'; });
}
function loadArticleDetailPage(id) {
  var el = document.getElementById('article-detail-page'); if (!el) return;
  if (String(id).indexOf('demo_') === 0) {
    var demos = demoArticleFallback({articleIds:[id]});
    var a = demos[0] || {title:'演示文章',content:'<p>演示数据，安装后可在后台编辑。</p>'};
    el.innerHTML = articleDetailCoverHtml(a) + '<h1 class="detail-title">' + (a.title||'') + '</h1><div class="detail-body">' + (a.content || '') + '</div>';
    return;
  }
  recordFootprint('article', id);
  fetch(apiBase + '/article/detail.php?id=' + encodeURIComponent(id)).then(function(r){ return r.json(); }).then(function(json){
    if (!json || json.code !== 0 || !json.data) { el.innerHTML = '<div class="article-empty">' + (json && json.message ? json.message : '加载失败') + '</div>'; return; }
    var a = json.data;
    el.innerHTML = articleDetailCoverHtml(a) + '<div class="detail-actions">' + favoriteBtnHtml('article', id) + '</div><h1 class="detail-title">' + (a.title||'') + '</h1><div class="detail-body">' + (a.content||'') + '</div>';
    refreshFavoriteBtn(el.querySelector('.detail-fav-btn'));
  }).catch(function(){ el.innerHTML = '<div class="article-empty">加载失败</div>'; });
}
function renderArticleDetailPage(query) {
  setTimeout(function(){ loadArticleDetailPage(query.id || ''); }, 0);
  return renderSubPageNav('文章详情') + '<div class="detail-page article-detail-page" id="article-detail-page"><div class="article-empty">加载中...</div></div>';
}
function articleListPageInner() {
  return '<div class="list-cat-bar" id="article-cat-bar"></div><div class="article-list"><div class="article-empty">加载中...</div></div>';
}
function renderListCatBar(container, cats, activeId, onPick) {
  if (!container) return;
  var html = '<button type="button" class="cat-chip' + (activeId === 0 ? ' active' : '') + '" data-id="0">全部</button>';
  (cats || []).forEach(function(c) {
    var id = parseInt(c.id, 10) || 0;
    html += '<button type="button" class="cat-chip' + (activeId === id ? ' active' : '') + '" data-id="' + id + '">' + (c.name || '') + '</button>';
  });
  container.innerHTML = html;
  container.querySelectorAll('.cat-chip').forEach(function(btn) {
    btn.addEventListener('click', function(){ onPick(parseInt(btn.getAttribute('data-id'), 10) || 0); });
  });
}
function renderListCatSidebar(container, cats, activeId, onPick) {
  if (!container) return;
  var html = '<button type="button" class="cat-side-item' + (activeId === 0 ? ' active' : '') + '" data-id="0">全部</button>';
  (cats || []).forEach(function(c) {
    var id = parseInt(c.id, 10) || 0;
    html += '<button type="button" class="cat-side-item' + (activeId === id ? ' active' : '') + '" data-id="' + id + '">' + (c.name || '') + '</button>';
  });
  container.innerHTML = html;
  container.querySelectorAll('.cat-side-item').forEach(function(btn) {
    btn.addEventListener('click', function(){ onPick(parseInt(btn.getAttribute('data-id'), 10) || 0); });
  });
}
function renderProductCatBar(container, cats, activeId, onPick, layout) {
  if (layout === 'sidebar') renderListCatSidebar(container, cats, activeId, onPick);
  else renderListCatBar(container, cats, activeId, onPick);
}
function paintArticleListPageDemo(el, layout, showCover) {
  var list = buildArticlePool([], { articleIds: ['demo_1','demo_2','demo_3','demo_4','demo_5'] });
  if (!list.length) { el.innerHTML = '<div class="article-empty">暂无文章</div>'; return; }
  el.innerHTML = list.map(function(a){ return renderArticleItem(a, layout || 'image-text', showCover !== false); }).join('');
}
function initArticleListPage(layout, showCover) {
  var box = document.getElementById('article-list-page'); if (!box) return;
  var el = box.querySelector('.article-list'); if (!el) return;
  var catBar = document.getElementById('article-cat-bar');
  var state = { categoryId: 0, layout: layout || 'image-text', showCover: showCover !== false };
  var rq = (window.__routeQuery || {});
  if (rq.category_id) state.categoryId = parseInt(rq.category_id, 10) || 0;
  function runLoader() {
    var load = createPagedLoader('article-list', {
      listEl: el,
      scrollRoot: box,
      emptyText: '暂无文章',
      buildUrl: function(page) {
        var url = apiBase + '/article/list.php?page=' + page + '&page_size=20';
        if (state.categoryId > 0) url += '&category_id=' + state.categoryId;
        return url;
      },
      appendItems: function(list) {
        el.insertAdjacentHTML('beforeend', list.map(function(a){ return renderArticleItem(a, state.layout, state.showCover); }).join(''));
      }
    });
    load(true);
  }
  if (guideCapture) {
    if (catBar) catBar.innerHTML = '<span class="cat-chip active">全部</span>';
    paintArticleListPageDemo(el, layout, showCover);
    return;
  }
  function bindCats(cats) {
    function pick(id) {
      state.categoryId = id;
      renderListCatBar(catBar, cats, state.categoryId, pick);
      runLoader();
    }
    renderListCatBar(catBar, cats, state.categoryId, pick);
  }
  fetch(apiBase + '/article/categories.php').then(function(r){ return r.json(); }).then(function(json) {
    var cats = (json && json.code === 0 && json.data && json.data.list) ? json.data.list : [];
    bindCats(cats);
    runLoader();
  }).catch(function(){ bindCats([]); runLoader(); });
}
function renderArticleListPage(query) {
  var title = query.title || '文章列表';
  var layout = query.layout || 'image-text';
  var showCover = query.showCover !== '0';
  setTimeout(function(){ initArticleListPage(layout, showCover); }, 0);
  return renderSubPageNav(title) + '<div class="article-list-page article-box layout-' + layout + '" id="article-list-page">' + articleListPageInner() + '</div>';
}
function productListPageInner(cols, categoryLayout, layout) {
  categoryLayout = categoryLayout || 'top';
  layout = layout || 'grid';
  var gridClass = layout === 'list' ? 'product-list layout-list' : (layout === 'row' ? 'product-row-list layout-row' : 'product-grid');
  var gridStyle = layout === 'grid' ? ' style="grid-template-columns:repeat(' + cols + ',1fr)"' : '';
  var grid = '<div class="' + gridClass + '"' + gridStyle + '><div class="article-empty">加载中...</div></div>';
  if (categoryLayout === 'sidebar') {
    return '<div class="product-list-body cat-layout-sidebar"><div class="list-cat-sidebar" id="product-cat-bar"></div><div class="product-list-main">' + grid + '</div></div>';
  }
  return '<div class="list-cat-bar" id="product-cat-bar"></div>' + grid;
}
function paintProductListPageDemo(el, props, layout) {
  var cols = props.columns || 2, rows = props.rows || 3, limit = cols * rows;
  var items = buildProductPool([], props).slice(0, Math.max(limit, 8));
  el.innerHTML = items.length ? items.map(function(p){ return productCardHtml(p, props, layout || 'grid'); }).join('') : '<div class="article-empty">暂无商品</div>';
}
function initProductListPage(layout, propsEnc) {
  var box = document.getElementById('product-list-page'); if (!box) return;
  var el = box.querySelector('.product-grid,.product-list,.product-row-list'); if (!el) return;
  var catBar = document.getElementById('product-cat-bar');
  var props = {};
  try { props = propsEnc ? JSON.parse(decodeURIComponent(propsEnc)) : {}; } catch (e) {}
  var catLayout = props.categoryLayout || 'top';
  var scrollRoot = catLayout === 'sidebar' ? (box.querySelector('.product-list-main') || box) : box;
  var state = { categoryId: 0, layout: layout || props.layout || 'grid', props: props, catLayout: catLayout };
  var rq = (window.__routeQuery || {});
  if (rq.category_id) state.categoryId = parseInt(rq.category_id, 10) || 0;
  function runLoader() {
    var load = createPagedLoader('product-list', {
      listEl: el,
      scrollRoot: scrollRoot,
      emptyText: '暂无商品',
      buildUrl: function(page) {
        var url = apiBase + '/product/list.php?page=' + page + '&page_size=20';
        if (state.categoryId > 0) url += '&category_id=' + state.categoryId;
        return url;
      },
      appendItems: function(list) {
        el.insertAdjacentHTML('beforeend', list.map(function(p){ return productCardHtml(p, state.props, state.layout); }).join(''));
      }
    });
    load(true);
  }
  if (guideCapture) {
    if (catBar) {
      if (catLayout === 'sidebar') catBar.innerHTML = '<button type="button" class="cat-side-item active">全部</button>';
      else catBar.innerHTML = '<span class="cat-chip active">全部</span>';
    }
    paintProductListPageDemo(el, props, layout || props.layout || 'grid');
    return;
  }
  function bindCats(cats) {
    function pick(id) {
      state.categoryId = id;
      renderProductCatBar(catBar, cats, state.categoryId, pick, state.catLayout);
      runLoader();
    }
    renderProductCatBar(catBar, cats, state.categoryId, pick, state.catLayout);
  }
  fetch(apiBase + '/product/categories.php').then(function(r){ return r.json(); }).then(function(json) {
    var cats = (json && json.code === 0 && json.data && json.data.list) ? json.data.list : [];
    bindCats(cats);
    runLoader();
  }).catch(function(){ bindCats([]); runLoader(); });
}
function renderProductListPage(query) {
  var title = query.title || '商品列表';
  var layout = query.layout || 'grid';
  var cols = parseInt(query.cols, 10) || 2;
  var props = {};
  try { props = query.props ? JSON.parse(decodeURIComponent(query.props)) : {}; } catch (e) {}
  var catLayout = props.categoryLayout || 'top';
  setTimeout(function(){ initProductListPage(layout, query.props || ''); }, 0);
  return renderSubPageNav(title) + '<div class="product-list-page product-box ' + layout + ' cat-layout-' + catLayout + '" id="product-list-page">' + productListPageInner(cols, catLayout, layout) + '</div>';
}
function loadProductDetailPage(id) {
  var el = document.getElementById('product-detail-page'); if (!el) return;
  recordFootprint('product', id);
  fetch(apiBase + '/product/detail.php?id=' + encodeURIComponent(id)).then(function(r){ return r.json(); }).then(function(json){
    if (!json || json.code !== 0 || !json.data) { el.innerHTML = '<div class="article-empty">' + (json && json.message ? json.message : '加载失败') + '</div>'; return; }
    var p = json.data;
    var img = p.image ? '<img class="detail-cover" src="' + productImg(p, false) + '" alt="">' : '';
    var fav = '<div class="detail-actions">' + favoriteBtnHtml('product', id) + '</div>';
    var buyBar = '<div class="detail-buy-bar"><button type="button" class="btn-add-cart" onclick="addToCart(' + p.id + ',1)">加入购物车</button><button type="button" class="btn-buy-now" onclick="buyNow(' + p.id + ')">立即购买</button></div>';
    el.innerHTML = img + fav + '<h1 class="detail-title">' + (p.name||'') + '</h1><div class="detail-price">¥' + (p.price||0) + '</div><div class="detail-desc">' + (p.description||'') + '</div>' + buyBar;
    refreshFavoriteBtn(el.querySelector('.detail-fav-btn'));
  }).catch(function(){ el.innerHTML = '<div class="article-empty">加载失败</div>'; });
}
function renderProductDetailPage(query) {
  setTimeout(function(){ loadProductDetailPage(query.id || ''); }, 0);
  return renderSubPageNav('商品详情') + '<div class="detail-page product-detail-page" id="product-detail-page"><div class="article-empty">加载中...</div></div>';
}
function renderVirtualPage(path, query) {
  if (path === 'search-article') return renderSearchArticlePage(query);
  if (path === 'search-product') return renderSearchProductPage(query);
  if (path === 'article-list') return renderArticleListPage(query);
  if (path === 'product-list') return renderProductListPage(query);
  if (path === 'article-detail') return renderArticleDetailPage(query);
  if (path === 'product-detail') return renderProductDetailPage(query);
  if (path === 'cart') return renderCartPage();
  if (path === 'checkout') return renderCheckoutPage(query);
  if (path === 'order' && window.HAS_COMMERCE) return renderOrderPage(query);
  if (path === 'order-list' && window.HAS_COMMERCE) return renderOrderPage(query);
  if (path === 'order-list') return renderOrderListPage(query);
  if (path === 'order-detail') return renderOrderDetailPage(query);
  if (path === 'address-list') return renderAddressListPage();
  if (path === 'settings') return renderSettingsPage();
  if (path === 'invite') return renderInvitePage();
  if (path === 'coupon-list') return renderCouponListPage();
  if (path === 'member-center') return renderMemberCenterPage();
  if (path === 'login') return renderLoginPage();
  if (path === 'register') return renderRegisterPage();
  if (path === 'forgot-password') return renderForgotPasswordPage();
  if (path === 'group-buy') return renderGroupBuyPage();
  if (path === 'flash-sale') return renderFlashSalePage();
  if (path === 'live-room') return renderLiveRoomPage();
  if (path === 'check-in') return renderCheckInPage();
  if (path === 'favorites-list') return renderFavoritesListPage();
  if (path === 'footprint-list') return renderFootprintListPage();
  if (path === 'hobbies-list') return renderHobbiesListPage();
  if (path === 'wallet-recharge') return renderWalletRechargePage();
  if (path === 'wallet-logs') return renderWalletLogsPage();
  if (path === 'points-logs') return renderPointsLogsPage();
  return '';
}
function resolvePageHeaderSearchType(props) {
  props = props || {};
  var searchType = props.searchType || '';
  if (searchType === 'article' || searchType === 'product') return searchType;
  var ph = (props.placeholder || '').trim();
  if (/文章|资讯|新闻/.test(ph)) return 'article';
  if (ph || props.showSearchBtn) return 'product';
  return '';
}
function buildPageHeaderInner(props) {
  var searchType = resolvePageHeaderSearchType(props);
  var enabled = searchType === 'article' || searchType === 'product';
  var ph = props.placeholder || '';
  if (!ph) {
    if (searchType === 'article') ph = '搜索文章';
    else if (searchType === 'product') ph = '搜索商品';
    else ph = '暂未开启搜索';
  }
  var scan = props.showScan !== false ? '<span>⌁</span>' : '';
  var msg = props.showMessage !== false ? '<span>💬</span>' : '';
  var searchBtn = props.showSearchBtn === true;
  var searchBtnText = props.searchBtnText || '搜索';
  var inputAttrs = enabled
    ? ' type="search" placeholder="' + ph + '" data-search-type="' + searchType + '" onkeydown="if(event.key===\'Enter\'){headerSearchSubmit(this.value,this.getAttribute(\'data-search-type\'))}"'
    : ' placeholder="' + ph + '" readonly disabled';
  var searchBtnHtml = searchBtn && enabled
    ? '<button type="button" class="header-search-btn" onclick="headerSearchSubmit((this.parentNode.querySelector(\'input\')||{}).value,(this.parentNode.querySelector(\'input\')||{}).getAttribute(\'data-search-type\'))">' + searchBtnText + '</button>'
    : '';
  var inputHtml = '<div class="search-wrap"><input' + inputAttrs + '/>' + searchBtnHtml + '</div>';
  return '<div class="header-top"><span class="brand">' + (props.brand||'') + '</span><span>' + scan + msg + '</span></div><div class="search-row"><span>📍 ' + (props.location||'') + '</span>' + inputHtml + '</div>';
}
function renderPageHeader(props, cid) {
  var boxId = wgBoxId('ph', cid);
  wgMount(boxId, cid, props, function(el, p) {
    el.style.background = p.bgColor || '#2ecc71';
    el.innerHTML = buildPageHeaderInner(p);
  });
  return '<div class="component page-header" id="' + boxId + '" style="background:' + (props.bgColor||'#2ecc71') + '"' + instAttr(cid) + '>' + buildPageHeaderInner(props) + '</div>';
}
function formatCountdown(endTime) {
  if (!endTime) return '00:00:00';
  var end = new Date(endTime).getTime();
  if (isNaN(end)) return '00:00:00';
  var diff = Math.max(0, end - Date.now());
  var h = Math.floor(diff / 3600000), m = Math.floor((diff % 3600000) / 60000), s = Math.floor((diff % 60000) / 1000);
  return String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
}
function mountCountdownEl(el, endTime) {
  if (!el) return;
  function tick() { el.textContent = formatCountdown(endTime); }
  tick();
  setInterval(tick, 1000);
}
function renderPromoPair(props, cid) {
  var boxId = 'pp-' + (cid || Math.random().toString(36).slice(2));
  var build = function(p) {
    var items = p.items || [];
    var cd = p.showCountdown ? '<div class="promo-countdown">' + formatCountdown(p.countdownEnd) + '</div>' : '';
    var cards = items.map(function(item, i) {
      var img = item.image || '';
      if (img) img = imgThumbUrl(img);
      var inner = (img ? '<img src="' + img + '" alt="">' : '') + '<div class="promo-title">' + (item.title||'') + '</div>';
      var href = resolveLinkHref(item.link);
      if (href.indexOf('#') === 0) return '<a class="promo-card" style="background:' + (item.bgColor||'#f5f5f5') + ';text-decoration:none;color:inherit;display:block" href="' + href + '" onclick="navByHref(\'' + href + '\', event)">' + inner + '</a>';
      return '<div class="promo-card" style="background:' + (item.bgColor||'#f5f5f5') + '">' + inner + '</div>';
    }).join('');
    return cd + '<div class="promo-pair-grid">' + cards + '</div>';
  };
  if (cid && !guideCapture) {
    wgMount(boxId, cid, props, function(el, p) {
      el.innerHTML = build(p);
      var cdEl = el.querySelector('.promo-countdown');
      if (cdEl) mountCountdownEl(cdEl, p.countdownEnd);
    });
  }
  var html = build(props);
  setTimeout(function() {
    var root = document.getElementById(boxId);
    if (!root) return;
    var cdEl = root.querySelector('.promo-countdown');
    if (cdEl) mountCountdownEl(cdEl, props.countdownEnd);
  }, 50);
  return '<div class="component promo-pair" id="' + boxId + '"' + instAttr(cid) + '>' + html + '</div>';
}
function productScrollCardHtml(p) {
  return '<div class="scroll-card" onclick="openProduct(' + p.id + ')"><img src="' + productImg(p) + '" alt=""><div class="product-name">' + (p.name||'') + '</div><div class="product-price">¥' + (p.price||0) + '</div></div>';
}
function renderProductScroll(props, cid) {
  var count = props.itemCount || 6;
  var items = (props.demoProducts || []).slice(0, count);
  var cards = items.map(productScrollCardHtml).join('');
  var cd = props.showCountdown !== false ? '<span class="scroll-countdown">' + formatCountdown(props.countdownEnd) + '</span>' : '';
  var boxId = 'ps-' + (cid || Math.random().toString(36).slice(2));
  var propsEnc = encodeURIComponent(JSON.stringify(props));
  setTimeout(function(){ refreshProductScrollFromAPI(boxId, props, cid); }, guideCapture ? 0 : 100);
  setTimeout(function() {
    var root = document.getElementById(boxId);
    if (!root) return;
    var cdEl = root.querySelector('.scroll-countdown');
    if (cdEl) mountCountdownEl(cdEl, props.countdownEnd);
  }, 60);
  return '<div class="component promo-scroll" id="' + boxId + '"' + instAttr(cid) + ' data-cid="' + (cid||'') + '" data-props="' + propsEnc + '"><div class="scroll-head"><strong style="color:#e74c3c">' + (props.title||'限时秒杀') + '</strong>' + cd + '</div><div class="scroll-track">' + (cards || '<div class="article-empty">加载中...</div>') + '</div></div>';
}
function refreshProductScrollFromAPI(boxId, props, cid) {
  if (guideCapture) {
    var count = props.itemCount || 6;
    var items = (props.demoProducts || []).slice(0, count);
    var track = document.querySelector('#' + boxId + ' .scroll-track');
    if (track) track.innerHTML = items.length ? items.map(productScrollCardHtml).join('') : '<div class="article-empty">暂无商品</div>';
    return;
  }
  var count = props.itemCount || 6;
  var url = apiBase + '/product/scroll_list.php?limit=' + count;
  if (cid) url += '&component_id=' + encodeURIComponent(cid);
  fetch(url).then(function(r){ return r.json(); }).then(function(json){
    var apiList = (json && json.code === 0 && json.data && json.data.list) ? json.data.list : [];
    var items = apiList.length ? apiList : (props.demoProducts || []).slice(0, count);
    var track = document.querySelector('#' + boxId + ' .scroll-track');
    if (track) track.innerHTML = items.length ? items.map(productScrollCardHtml).join('') : '<div class="article-empty">暂无商品</div>';
  }).catch(function(){});
}
function renderPromoBanner(props, cid) {
  var cols = props.columns || 2, rows = props.rows || 2, limit = cols * rows;
  var items = (props.demoProducts || []).slice(0, limit);
  var banner = props.bannerImage ? '<img src="' + imgThumbUrl(props.bannerImage) + '" alt="">' : '';
  var cards = items.map(function(p) {
    var pid = p.id || 0;
    var cartClick = 'event.stopPropagation();if(typeof addToCart===\'function\'){addToCart(' + pid + ',1);}return false;';
    return '<div class="product-card" onclick="openProduct(' + pid + ')"><img src="' + productImg(p) + '" alt=""><div class="product-name">' + (p.name||'') + '</div><div class="product-price">¥' + (p.price||0) + '</div><button class="product-add-cart" onclick="' + cartClick + '">+</button></div>';
  }).join('');
  var boxId = 'pb-banner-' + (cid || Math.random().toString(36).slice(2));
  var build = function(p, list) {
    var lim = (p.columns||2) * (p.rows||2);
    var prods = (list || p.demoProducts || []).slice(0, lim);
    var ban = p.bannerImage ? '<img src="' + imgThumbUrl(p.bannerImage) + '" alt="">' : banner;
    var grid = prods.map(function(pr) {
      var pid = pr.id || 0;
      var cartClick = 'event.stopPropagation();if(typeof addToCart===\'function\'){addToCart(' + pid + ',1);}return false;';
      return '<div class="product-card" onclick="openProduct(' + pid + ')"><img src="' + productImg(pr) + '" alt=""><div class="product-name">' + (pr.name||'') + '</div><div class="product-price">¥' + (pr.price||0) + '</div><button class="product-add-cart" onclick="' + cartClick + '">+</button></div>';
    }).join('');
    return '<div class="banner-row" style="background:' + (p.bannerBgColor||'#e8f5e9') + '">' + ban + '<div style="padding:10px"><strong>' + (p.title||'') + '</strong><div style="font-size:12px;color:#666">' + (p.subtitle||'') + '</div></div></div><div class="banner-grid" style="grid-template-columns:repeat(' + (p.columns||2) + ',1fr)">' + grid + '</div>';
  };
  if (cid && !guideCapture) {
    fetch(apiBase + '/promo_banner/get.php?id=' + encodeURIComponent(cid)).then(function(r){ return r.json(); }).then(function(j){
      var el = document.getElementById(boxId);
      if (!el || !j || j.code !== 0 || !j.data) return;
      var merged = Object.assign({}, props, j.data.props || {});
      el.innerHTML = build(merged, j.data.products || []);
    }).catch(function(){});
  }
  return '<div class="component promo-banner" id="' + boxId + '"' + instAttr(cid) + '>' + build(props, items) + '</div>';
}
function productBadgeHtml(p, props, index) {
  if (p.is_flash_sale) return '<span class="product-flash-badge">限时</span>';
  if (props.showProductBadge === false) return '';
  var mode = props.productBadgeMode || 'featured';
  if (mode === 'off') return '';
  var text = props.productBadgeText || '热卖';
  var show = mode === 'all';
  if (!show && mode === 'featured') {
    if (p.is_featured || p.is_featured === 1) show = true;
    else if (index >= 0 && index < 4) show = true;
  }
  return show ? '<span class="product-flash-badge">' + text + '</span>' : '';
}
function productCardHtml(p, props, layout, index) {
  var img = productImg(p);
  var price = props.showPrice !== false ? '<div class="product-price">¥' + (p.price||0) + '</div>' : '';
  var stockNum = parseInt(p.stock, 10);
  var stock = (!isNaN(stockNum) && stockNum >= 0) ? '<div class="product-stock">库存 ' + stockNum + '</div>' : '';
  var flashBadge = productBadgeHtml(p, props, index == null ? -1 : index);
  var flashCd = (p.is_flash_sale && p.flash_end_at) ? '<span class="product-flash-cd" data-end="' + p.flash_end_at + '"></span>' : '';
  var desc = p.description ? '<div class="product-desc">' + String(p.description).slice(0, 48) + '</div>' : '';
  var prog = (layout === 'list' && props.showProgress) ? '<div style="font-size:10px;color:#e74c3c">已抢' + (((p.id||1)*17)%60+35) + '%</div>' : '';
  var cartColor = props.addCartColor || '#e74c3c';
  var showCart = props.showAddCart !== false;
  var showBuy = props.showBuyNow !== false;
  var cartClick = 'event.stopPropagation();if(typeof addToCart===\'function\'){addToCart(' + p.id + ',1);}else{showH5Toast(\'购物功能未加载，请重新 Build 部署\');}return false;';
  var addCartLabel = props.addCartText || '加入购物车';
  if (addCartLabel === '去结算') addCartLabel = '加入购物车';
  var listCart = showCart ? '<button onclick="' + cartClick + '" style="background:' + cartColor + ';color:#fff;border:none;border-radius:12px;padding:4px 8px;font-size:11px">' + addCartLabel + '</button>' : '';
  var buyNowBtn = showBuy ? '<button onclick="event.stopPropagation();if(typeof buyNow===\'function\'){buyNow(' + p.id + ');}return false;" style="background:#e74c3c;color:#fff;border:none;border-radius:12px;padding:4px 8px;font-size:11px;margin-left:4px">' + (props.buyNowText||'立即购买') + '</button>' : '';
  var cartStyle = props.addCartStyle || 'plus';
  var gridCart = '';
  if (showCart && layout === 'grid') {
    if (cartStyle === 'text' || cartStyle === 'buyNow') {
      var btnText = props.addCartText || (cartStyle === 'buyNow' ? '立即购买' : '加入购物车');
      if (btnText === '去结算') btnText = '加入购物车';
      var clickFn = cartStyle === 'buyNow' ? 'event.stopPropagation();buyNow(' + p.id + ');return false;' : cartClick;
      gridCart = '<button class="product-grid-cart-text" onclick="' + clickFn + '" style="background:' + cartColor + '">' + btnText + '</button>';
    } else {
      gridCart = '<button class="product-add-cart" onclick="' + cartClick + '" style="background:' + cartColor + '">+</button>';
    }
  }
  if (layout === 'row') return '<div class="product-card product-row" onclick="openProduct(' + p.id + ')">' + flashBadge + '<img src="' + img + '" alt=""><div class="product-row-body"><div class="product-name">' + (p.name||'') + flashCd + '</div>' + desc + '<div class="product-row-foot">' + price + stock + listCart + buyNowBtn + '</div></div></div>';
  if (layout === 'list') return '<div class="product-card" onclick="openProduct(' + p.id + ')"><img src="' + img + '" alt=""><div><div class="product-name">' + (p.name||'') + '</div>' + prog + price + listCart + buyNowBtn + '</div></div>';
  var gridActions = (listCart || buyNowBtn) ? ('<div class="product-grid-actions">' + listCart + buyNowBtn + '</div>') : gridCart;
  return '<div class="product-card" onclick="openProduct(' + p.id + ')">' + flashBadge + '<img src="' + img + '" alt=""><div class="product-name">' + (p.name||'') + '</div>' + price + (gridActions || gridCart) + '</div>';
}
function isLegacyDemoProductImage(url) {
  url = String(url || '').toLowerCase();
  return !url || url.indexOf('data:') === 0 || url.indexOf('./assets/demo/p') >= 0 || url.indexOf('assets/demo/p') >= 0;
}
function mergeProductImagesFromProps(list, props) {
  var map = {};
  (props.demoProducts || []).forEach(function(p){ if (p && p.id != null && p.image) map[String(p.id)] = p.image; });
  return (list || []).map(function(p){
    var img = map[String(p.id)];
    if (img && isLegacyDemoProductImage(p.image)) return Object.assign({}, p, {image: img});
    return p;
  });
}
function buildProductPool(apiList, props) {
  if (apiList && apiList.length) return mergeProductImagesFromProps(apiList, props).slice(0, 50);
  var cols = props.columns || 2, rows = props.rows || 3;
  return (props.demoProducts || []).slice(0, Math.max(cols * rows, 30));
}
function productListMoreHref(cid, props) {
  var title = (props && props.title) ? props.title : '商品列表';
  var layout = (props && props.layout) ? props.layout : 'grid';
  var cols = (props && props.columns) ? props.columns : 2;
  var q = 'title=' + encodeURIComponent(title) + '&layout=' + encodeURIComponent(layout) + '&cols=' + cols + '&props=' + encodeURIComponent(JSON.stringify(props || {}));
  if (cid) q += '&cid=' + encodeURIComponent(cid);
  return '#product-list?' + q;
}
function productDisplayLimit(props) {
  props = props || {};
  var layout = props.layout || 'grid';
  var rows = Math.max(1, props.rows || 3);
  if (layout === 'list' || layout === 'row') return rows;
  var cols = Math.max(1, props.columns || 2);
  return cols * rows;
}
function renderProduct(props, cid) {
  if (props.listMode === 'full') return renderProductFullPage(props, cid);
  var cols = props.columns || 2;
  var limit = productDisplayLimit(props);
  var items = (props.demoProducts || []).slice(0, limit);
  var layout = props.layout || 'grid';
  var title = props.title || '商品列表';
  var showMore = props.showMore !== false;
  var moreHref = productListMoreHref(cid, props);
  var headHtml = showMore
    ? '<div class="product-section-head"><span class="product-section-title">' + title + '</span><a class="product-section-more" href="' + moreHref + '" onclick="navTo(\'' + moreHref.slice(1) + '\');return false;">更多</a></div>'
    : '<div class="product-section-head"><span class="product-section-title">' + title + '</span></div>';
  var cards = items.map(function(p, i){ return productCardHtml(p, props, layout, i); }).join('');
  var boxId = 'pb-' + (cid || Math.random().toString(36).slice(2));
  var propsEnc = encodeURIComponent(JSON.stringify(props));
  setTimeout(function(){ refreshProductsFromAPI(boxId, props, cid); }, guideCapture ? 0 : 100);
  return '<div class="component product-box ' + layout + '" id="' + boxId + '"' + instAttr(cid) + ' data-cid="' + (cid||'') + '" data-props="' + propsEnc + '">' + headHtml + '<div class="product-grid" style="grid-template-columns:repeat(' + cols + ',1fr)">' + (cards || '<div class="article-empty">加载中...</div>') + '</div></div>';
}
function renderProductFullPage(props, cid) {
  var layout = props.layout || 'grid';
  var cols = props.columns || 2;
  var catLayout = props.categoryLayout || 'top';
  var propsEnc = encodeURIComponent(JSON.stringify(props || {}));
  setTimeout(function(){ initProductListPage(layout, propsEnc); }, 0);
  return '<div class="product-list-page product-box ' + layout + ' cat-layout-' + catLayout + '" id="product-list-page"' + instAttr(cid) + '>' + productListPageInner(cols, catLayout, layout) + '</div>';
}
function resolveProductComponentId(cid) {
  if (!cid) return '';
  if (cid === 'group_buy_locked_product' || cid === 'flash_sale_locked_product') {
    var entry = (window.__routeQuery || {}).entry || '';
    if (entry) return entry;
  }
  return cid;
}
function refreshProductsFromAPI(boxId, props, cid) {
  if (guideCapture) {
    var limit = productDisplayLimit(props);
    var items = buildProductPool([], props).slice(0, limit);
    var grid = document.querySelector('#' + boxId + ' .product-grid');
    if (grid) grid.innerHTML = items.length ? items.map(function(p){ return productCardHtml(p, props, props.layout||'grid'); }).join('') : '<div class="article-empty">暂无商品</div>';
    return;
  }
  var fallbackLimit = productDisplayLimit(props);
  var url = apiBase + '/product/list.php?limit=50';
  var apiCid = resolveProductComponentId(cid);
  if (apiCid) url += '&component_id=' + encodeURIComponent(apiCid);
  fetch(url).then(function(r){ return r.json(); }).then(function(json){
    var limit = (json && json.data && json.data.show_limit) ? json.data.show_limit : fallbackLimit;
    var box = document.getElementById(boxId); if (box) box.dataset.showLimit = String(limit);
    var apiList = (json && json.code === 0 && json.data && json.data.list) ? json.data.list : [];
    var pool = buildProductPool(apiList, props);
    var items = pool.slice(0, limit);
    var grid = document.querySelector('#' + boxId + ' .product-grid');
    if (!grid) return;
    if (!items.length) { grid.innerHTML = '<div class="article-empty">暂无商品</div>'; return; }
    var layout = props.layout || 'grid';
    grid.innerHTML = items.map(function(p){ return productCardHtml(p, props, layout); }).join('');
  }).catch(function(){
    var grid = document.querySelector('#' + boxId + ' .product-grid'); if (!grid) return;
    var items = buildProductPool([], props).slice(0, fallbackLimit);
    if (!items.length) { grid.innerHTML = '<div class="article-empty">暂无商品</div>'; return; }
    grid.innerHTML = items.map(function(p){ return productCardHtml(p, props, props.layout||'grid'); }).join('');
  });
}
function renderRichText(props, cid) {
  var boxId = 'rt-' + (cid || Math.random().toString(36).slice(2));
  setTimeout(function(){ loadRichText(boxId, cid, props); }, guideCapture ? 0 : 30);
  return '<div class="component rich-text-box" id="' + boxId + '"' + instAttr(cid) + '>加载中...</div>';
}
function loadRichText(boxId, cid, fallback) {
  if (guideCapture) { paintRichText(boxId, fallback); return; }
  if (!cid) { paintRichText(boxId, fallback); return; }
  fetch(apiBase + '/richtext/get.php?id=' + encodeURIComponent(cid)).then(function(r){ return r.json(); }).then(function(j){
    if (j && j.code === 0 && j.data) paintRichText(boxId, j.data); else paintRichText(boxId, fallback);
  }).catch(function(){ paintRichText(boxId, fallback); });
}
function paintRichText(boxId, data) {
  data = data || {};
  var el = document.getElementById(boxId);
  if (!el) return;
  el.innerHTML = data.content || '<p>暂无内容</p>';
}
function renderGridNav(props) {
  var style = props.gridStyle || 'grid';
  var cols = props.columns || 4;
  var items = props.items || [];
  if (style === 'magic') items = items.slice(0, 3);
  var head = '';
  if (props.title) head = '<div class="grid-nav-head"><strong>' + props.title + '</strong>' + (props.subtitle ? '<span>' + props.subtitle + '</span>' : '') + '</div>';
  var cells = items.map(function(item, idx) {
    var iconSrc = item.icon ? imgThumbUrl(item.icon) : '';
    var icon = iconSrc ? '<img class="grid-icon" src="' + iconSrc + '" alt="">' : '<div class="grid-icon grid-icon-ph"></div>';
    var inner = '';
    if (style === 'magic' && idx === 0) {
      var coverImg = iconSrc ? '<img class="magic-main-img" src="' + iconSrc + '" alt="">' : '<div class="magic-main-ph"></div>';
      inner = '<div class="magic-main-cover">' + coverImg + '</div><span class="magic-main-label">' + (item.text||'') + '</span>';
    } else {
      inner = icon + '<span class="grid-text">' + (item.text||'') + '</span>';
    }
    var magicCls = '';
    if (style === 'magic') {
      if (idx === 0) magicCls = ' magic-main';
      else if (idx === 1) magicCls = ' magic-r1';
      else if (idx === 2) magicCls = ' magic-r2';
    }
    var cardBg = style === 'card' ? ' style="background:' + (item.bgColor || '#f5f7fa') + '"' : '';
    var href = resolveLinkHref(item.link);
    var gp = item.text ? ' data-guide-point="mine:grid:' + String(item.text).replace(/"/g,'') + '"' : '';
    if (href.indexOf('#') === 0) return '<a class="grid-item' + magicCls + '" href="' + href + '"' + cardBg + gp + ' onclick="navByHref(\'' + href + '\', event)">' + inner + '</a>';
    if (href) return '<a class="grid-item' + magicCls + '" href="' + href + '"' + cardBg + gp + ' target="_blank" rel="noopener">' + inner + '</a>';
    return '<div class="grid-item' + magicCls + '"' + cardBg + gp + '>' + inner + '</div>';
  }).join('');
  var gridStyle = '';
  if (style === 'magic') gridStyle = 'grid-template-columns:2fr 1fr';
  else if (style === 'grid') gridStyle = 'grid-template-columns:repeat(' + cols + ',1fr)';
  return '<div class="component grid-nav style-' + style + '" style="' + gridStyle + '">' + head + cells + '</div>';
}
function demoArticleFallback(props) {
  var demos = [
    {id:'demo_1',title:'欢迎使用资讯模块',summary:'部署后可在后台编辑',cover:'./assets/demo/a01.svg',view_count:128,created_at:'2026-01-01',is_demo:1,content:'<p><strong>本文为演示数据</strong>，安装后可在 PHP 后台编辑。</p>'},
    {id:'demo_2',title:'春季新品发布说明',summary:'演示文章仅供参考',cover:'./assets/demo/a02.svg',view_count:86,created_at:'2026-01-02',is_demo:1,content:'<p>演示正文。</p>'},
    {id:'demo_3',title:'会员权益与积分规则',summary:'演示数据',cover:'./assets/demo/a03.svg',view_count:203,created_at:'2026-01-03',is_demo:1,content:'<p>演示正文。</p>'},
    {id:'demo_4',title:'物流配送时效说明',summary:'演示数据',cover:'./assets/demo/a04.svg',view_count:57,created_at:'2026-01-04',is_demo:1,content:'<p>演示正文。</p>'},
    {id:'demo_5',title:'售后服务政策',summary:'演示数据',cover:'./assets/demo/a05.svg',view_count:91,created_at:'2026-01-05',is_demo:1,content:'<p>演示正文。</p>'}
  ];
  var ids = props.articleIds || [];
  if (!ids.length) return demos;
  var map = {}; demos.forEach(function(d){ map[String(d.id)] = d; });
  return ids.map(function(id){ return map[String(id)] || {id:id,title:'文章',summary:'',cover:'',view_count:0,created_at:''}; });
}
function articleIndustryId() {
  return (window.APP_CONFIG && window.APP_CONFIG.industry) || 'fresh_grocery';
}
function stockArticleCoverPath(industry, articleIndex) {
  var variant = ((Math.max(1, articleIndex) - 1) % 8) + 1;
  if (variant <= 3) return '/uploads/stock/' + industry + '_' + (6 + variant) + '.jpg';
  return '/uploads/stock/' + industry + '_' + (44 + variant) + '.jpg';
}
function stockSlotFromLegacy(kind, index) {
  if (kind === 'i') { if (index >= 0 && index < 6) return 1 + index; if (index >= 6 && index < 12) return 55 + (index - 6); }
  if (kind === 'b') { if (index >= 0 && index < 3) return 7 + index; if (index >= 3 && index < 8) return 48 + (index - 3); }
  if (kind === 'd' && index >= 0 && index < 30) return 10 + index;
  if (kind === 'p' && index >= 0 && index < 8) return 40 + index;
  if (kind === 'g' && index >= 0 && index < 2) return 53 + index;
  return 0;
}
function normalizeLegacyStockPath(cover) {
  var s = String(cover || '');
  var origin = '';
  var m0 = s.match(/^(https?:\/\/[^/]+)(\/uploads\/stock\/.*)$/i);
  if (m0) { origin = m0[1]; s = m0[2]; }
  var m = s.match(/\/uploads\/stock\/([a-z_]+)_([bipdg])(\d{2})\.jpg/i);
  if (!m) return cover;
  var slot = stockSlotFromLegacy(m[2].toLowerCase(), parseInt(m[3], 10));
  if (!slot) return cover;
  return origin + '/uploads/stock/' + m[1] + '_' + slot + '.jpg';
}
function needsArticleCoverRemap(cover, industry) {
  if (!cover) return true;
  var lower = String(cover).toLowerCase();
  if (lower.indexOf('./assets/demo/a') >= 0 || lower.indexOf('assets/demo/a') >= 0) return true;
  if (lower.indexOf('./assets/') === 0 && lower.indexOf('_b') < 0 && lower.indexOf('_d') >= 0) return true;
  cover = normalizeLegacyStockPath(cover);
  var legacy = String(cover).match(/\/uploads\/stock\/([a-z_]+)_([bipdg])(\d{2})\.jpg/i);
  if (legacy) return true;
  var m = String(cover).match(/\/uploads\/stock\/([a-z_]+)_(\d+)\.jpg/i);
  if (!m) return false;
  if (m[1] !== industry) return true;
  var slot = parseInt(m[2], 10);
  return slot >= 10 && slot <= 39;
}
function rewriteArticleCoverIndustry(cover, industry) {
  cover = normalizeLegacyStockPath(cover);
  var m = String(cover || '').match(/\/uploads\/stock\/([a-z_]+)_(\d+)\.jpg/i);
  if (!m || m[1] === industry) return cover;
  return '/uploads/stock/' + industry + '_' + m[2] + '.jpg';
}
function normalizeArticleCover(cover, id) {
  var sid = String(id || '');
  var industry = articleIndustryId();
  var catalog = window.DEMO_ARTICLE_COVERS || {};
  var demoIdx = sid.indexOf('demo_') === 0 ? (parseInt(sid.replace('demo_', ''), 10) || 1) : 0;
  if (needsArticleCoverRemap(cover, industry)) {
    if (catalog[sid]) return packStockAssetPath(catalog[sid]);
    if (demoIdx > 0) return packStockAssetPath(stockArticleCoverPath(industry, demoIdx));
  }
  var rewritten = rewriteArticleCoverIndustry(cover, industry);
  if (rewritten !== cover) return packStockAssetPath(rewritten);
  if (!cover && catalog[sid]) return packStockAssetPath(catalog[sid]);
  return packStockAssetPath(cover || '');
}
function packStockAssetPath(u) {
  if (!u) return u;
  var m = String(u).match(/\/uploads\/(stock|images)\/([^?#]+)/i);
  if (m) return './assets/images/' + m[2];
  return u;
}
function articleCoverUrl(cover, id) {
  var sid = String(id || '');
  cover = normalizeArticleCover(cover, sid);
  if (!cover) {
    if (sid.indexOf('demo_') === 0) {
      var n = parseInt(sid.replace('demo_', ''), 10) || 1;
      return assetUrl(packStockAssetPath(stockArticleCoverPath(articleIndustryId(), n)));
    }
    return '';
  }
  if (cover.indexOf('http') === 0 || cover.indexOf('data:') === 0) return cover;
  if (cover.indexOf('./') === 0 || cover.indexOf('/') === 0) return assetUrl(cover);
  return assetUrl('./' + cover);
}
function isLegacyDemoArticleCover(url) {
  return needsArticleCoverRemap(url, articleIndustryId());
}
function mergeArticleCoversFromCatalog(list) {
  return (list || []).map(function(a){
    var normalized = normalizeArticleCover(a.cover, a.id);
    if (normalized && normalized !== a.cover) return Object.assign({}, a, {cover: normalized});
    return a;
  });
}
function articleImgError(img, id) {
  img.onerror = null;
  var fallback = articleCoverUrl('', id) || './assets/demo/a01.svg';
  if (img.src !== fallback) img.src = fallback;
}
function buildArticlePool(apiList, props) {
  if (apiList && apiList.length) return mergeArticleCoversFromCatalog(apiList).slice(0, 50);
  return mergeArticleCoversFromCatalog(demoArticleFallback(props)).slice(0, 50);
}
function articleListMoreHref(cid, props) {
  var title = (props && props.title) ? props.title : '文章列表';
  var layout = (props && props.layout) ? props.layout : 'image-text';
  var showCover = (props && props.showCover === false) ? '0' : '1';
  var q = 'title=' + encodeURIComponent(title) + '&layout=' + encodeURIComponent(layout) + '&showCover=' + showCover;
  if (cid) q += '&cid=' + encodeURIComponent(cid);
  return '#article-list?' + q;
}
function renderArticle(props, cid) {
  if (props.listMode === 'full') return renderArticleFullPage(props, cid);
  var boxId = 'ab-' + (cid || Math.random().toString(36).slice(2));
  var layout = props.layout || 'image-text';
  var title = props.title || '文章列表';
  var showMore = props.showMore !== false;
  var moreHref = articleListMoreHref(cid, props);
  var moreHtml = showMore ? '<a class="article-section-more" href="' + moreHref + '" onclick="navTo(\'' + moreHref.slice(1) + '\');return false;">更多</a>' : '';
  var headHtml = '<div class="article-section-head"><span class="article-section-title">' + title + '</span>' + moreHtml + '</div>';
  setTimeout(function(){ refreshArticlesFromAPI(boxId, props, cid); }, guideCapture ? 0 : 80);
  return '<div class="component article-box layout-' + layout + '" id="' + boxId + '"' + instAttr(cid) + ' data-cid="' + (cid||'') + '">' + headHtml + '<div class="article-list"><div class="article-empty">加载中...</div></div></div>';
}
function renderArticleFullPage(props, cid) {
  var layout = props.layout || 'image-text';
  var showCover = props.showCover !== false;
  setTimeout(function(){ initArticleListPage(layout, showCover); }, 0);
  return '<div class="article-list-page article-box layout-' + layout + '" id="article-list-page"' + instAttr(cid) + '>' + articleListPageInner() + '</div>';
}
function renderArticleItem(a, layout, showCover) {
  var date = (a.created_at || '').slice(0, 10);
  var demoTag = (a.is_demo == 1) ? '<span class="article-demo-tag">演示</span>' : '';
  var summary = a.summary ? '<div class="article-summary">' + a.summary + '</div>' : '';
  var meta = '<div class="article-meta">' + date + (a.view_count ? (' · 阅读 ' + a.view_count) : '') + '</div>';
  var click = 'onclick="openArticle(' + (String(a.id).indexOf('demo_')===0 ? ('\''+a.id+'\'') : a.id) + ')"';
  var fullCover = articleCoverUrl(a.cover, a.id);
  var thumbCover = imgThumbUrl(fullCover);
  var imgErr = ' onerror="articleImgError(this,\'' + String(a.id).replace(/'/g,'') + '\')"';
  if (layout === 'big-image') {
    var cover = showCover && fullCover ? '<img class="article-cover-big" src="' + fullCover + '" alt=""' + imgErr + '>' : '';
    return '<div class="article-item big-image" ' + click + '>' + cover + '<div class="article-body"><div class="article-title">' + (a.title||'') + demoTag + '</div>' + summary + meta + '</div></div>';
  }
  var cover = showCover && thumbCover ? '<img class="article-cover" src="' + thumbCover + '" alt=""' + imgErr + '>' : '';
  return '<div class="article-item image-text" ' + click + '>' + cover + '<div class="article-body"><div class="article-title">' + (a.title||'') + demoTag + '</div>' + summary + meta + '</div></div>';
}
function paintArticleList(boxId, list, props) {
  var box = document.getElementById(boxId); if (!box) return;
  var listEl = box.querySelector('.article-list');
  if (!list.length) { listEl.innerHTML = '<div class="article-empty">暂无文章</div>'; return; }
  var layout = props.layout || 'image-text';
  var showCover = props.showCover !== false;
  listEl.innerHTML = list.map(function(a){ return renderArticleItem(a, layout, showCover); }).join('');
}
function refreshArticlesFromAPI(boxId, props, cid) {
  if (guideCapture) {
    var step = props.limit || 5;
    paintArticleList(boxId, buildArticlePool([], props).slice(0, step), props);
    return;
  }
  var url = apiBase + '/article/list.php?limit=50';
  if (cid) url += '&component_id=' + encodeURIComponent(cid);
  fetch(url).then(function(r){ return r.json(); }).then(function(json){
    var data = (json && json.code === 0 && json.data) ? json.data : {};
    var box = document.getElementById(boxId);
    if (box) {
      var titleEl = box.querySelector('.article-section-title');
      var moreEl = box.querySelector('.article-section-more');
      if (data.label && titleEl) titleEl.textContent = data.label;
      var showMore = data.show_more !== 0 && data.show_more !== false;
      if (moreEl) moreEl.style.display = showMore ? '' : 'none';
      else if (showMore && !box.querySelector('.article-section-more')) {
        var head = box.querySelector('.article-section-head');
        if (head) {
          var mh = articleListMoreHref(cid, props);
          head.insertAdjacentHTML('beforeend', '<a class="article-section-more" href="' + mh + '" onclick="navTo(\'' + mh.slice(1) + '\');return false;">更多</a>');
        }
      }
    }
    var step = data.show_limit ? data.show_limit : (props.limit || 5);
    var apiList = data.list ? data.list : [];
    var pool = buildArticlePool(apiList, props);
    paintArticleList(boxId, pool.slice(0, step), props);
  }).catch(function(){
    var step = props.limit || 5;
    var pool = buildArticlePool([], props);
    paintArticleList(boxId, pool.slice(0, step), props);
  });
}
function formEsc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); }
function renderFormField(f) {
  var label = formEsc(f.label || f.key);
  var req = f.required ? '<span class="form-req">*</span>' : '';
  var ph = formEsc(f.placeholder || '');
  var reqMsg = formEsc(ph || ('请输入' + (f.label || f.key)));
  var key = formEsc(f.key);
  var type = f.type || 'text';
  var reqAttr = f.required ? ' required data-required-msg="' + reqMsg + '"' : '';
  var head = '<div class="form-field"><label class="form-label">' + req + label + '</label>';
  if (type === 'textarea') {
    var rows = Math.max(2, Math.min(12, parseInt(f.rows, 10) || 4));
    return head + '<textarea name="' + key + '" rows="' + rows + '" placeholder="' + ph + '"' + reqAttr + '></textarea></div>';
  }
  if (type === 'date') {
    var dph = ph || '2025-11-11';
    return head + '<input type="text" class="form-date-input" name="' + key + '" placeholder="' + dph + '" autocomplete="off" maxlength="10"' + reqAttr + '><p class="form-format-hint">格式：' + dph + '（年-月-日）</p></div>';
  }
  if (type === 'datetime') {
    var dtph = ph || '2022-11-11 11:11:11';
    return head + '<input type="text" class="form-datetime-input" name="' + key + '" placeholder="' + dtph + '" autocomplete="off" maxlength="19"' + reqAttr + '><p class="form-format-hint">格式：' + dtph + '（年-月-日 时:分:秒）</p></div>';
  }
  if (type === 'radio') {
    var ropts = (f.options || []).map(function(o){ return '<label class="form-option"><input type="radio" name="' + key + '" value="' + formEsc(o.value) + '"' + reqAttr + '><span>' + formEsc(o.label) + '</span></label>'; }).join('');
    return head + '<div class="form-options">' + ropts + '</div></div>';
  }
  if (type === 'checkbox') {
    var copts = (f.options || []).map(function(o){ return '<label class="form-option"><input type="checkbox" name="' + key + '" value="' + formEsc(o.value) + '"><span>' + formEsc(o.label) + '</span></label>'; }).join('');
    return head + '<div class="form-options">' + copts + '</div></div>';
  }
  if (type === 'select') {
    var sel = '<option value="">' + (ph || '请选择') + '</option>';
    sel += (f.options || []).map(function(o){ return '<option value="' + formEsc(o.value) + '">' + formEsc(o.label) + '</option>'; }).join('');
    return head + '<select name="' + key + '"' + reqAttr + '>' + sel + '</select></div>';
  }
  var inputType = type === 'phone' ? 'tel' : (type === 'number' ? 'number' : (type === 'email' ? 'email' : 'text'));
  return head + '<input type="' + inputType + '" name="' + key + '" placeholder="' + ph + '"' + reqAttr + '></div>';
}
function formPad2(n) { n = parseInt(n, 10) || 0; return (n < 10 ? '0' : '') + n; }
function formPickerValue(y, m, d, h, mi, s, mode) {
  var date = y + '-' + formPad2(m) + '-' + formPad2(d);
  if (mode === 'date') return date;
  return date + ' ' + formPad2(h) + ':' + formPad2(mi) + ':' + formPad2(s);
}
function formParseDateValue(val, mode) {
  var now = new Date();
  var y = now.getFullYear(), m = now.getMonth() + 1, d = now.getDate(), h = now.getHours(), mi = now.getMinutes(), s = now.getSeconds();
  if (mode === 'date' && /^\d{4}-\d{2}-\d{2}$/.test(val)) {
    var p = val.split('-'); y = parseInt(p[0],10); m = parseInt(p[1],10); d = parseInt(p[2],10);
  }
  if (mode === 'datetime' && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(val)) {
    var parts = val.split(' '); var dp = parts[0].split('-'); var tp = parts[1].split(':');
    y = parseInt(dp[0],10); m = parseInt(dp[1],10); d = parseInt(dp[2],10);
    h = parseInt(tp[0],10); mi = parseInt(tp[1],10); s = parseInt(tp[2],10);
  }
  return { y:y, m:m, d:d, h:h, mi:mi, s:s };
}
function formSelectOptions(min, max, cur, suffix) {
  var html = '';
  for (var i = min; i <= max; i++) {
    html += '<option value="' + i + '"' + (i === cur ? ' selected' : '') + '>' + formPad2(i) + (suffix || '') + '</option>';
  }
  return html;
}
function showFormDatePicker(input, mode) {
  document.querySelectorAll('.form-picker-overlay').forEach(function(el){ el.remove(); });
  var parsed = formParseDateValue(input.value || '', mode);
  var overlay = document.createElement('div');
  overlay.className = 'form-picker-overlay';
  var yearOpts = '';
  for (var y = parsed.y - 50; y <= parsed.y + 10; y++) yearOpts += '<option value="' + y + '"' + (y === parsed.y ? ' selected' : '') + '>' + y + '年</option>';
  var timeRow = '';
  if (mode === 'datetime') {
    timeRow = '<div class="form-picker-label">时 / 分 / 秒</div><div class="form-picker-row"><select class="fp-h">' + formSelectOptions(0, 23, parsed.h, '时') + '</select><select class="fp-mi">' + formSelectOptions(0, 59, parsed.mi, '分') + '</select><select class="fp-s">' + formSelectOptions(0, 59, parsed.s, '秒') + '</select></div>';
  }
  var preview = formPickerValue(parsed.y, parsed.m, parsed.d, parsed.h, parsed.mi, parsed.s, mode);
  overlay.innerHTML = '<div class="form-picker-panel"><div class="form-picker-title">' + (mode === 'date' ? '选择日期' : '选择日期时间') + '</div><div class="form-picker-preview fp-preview">' + preview + '</div><div class="form-picker-label">年 / 月 / 日</div><div class="form-picker-row"><select class="fp-y">' + yearOpts + '</select><select class="fp-m">' + formSelectOptions(1, 12, parsed.m, '月') + '</select><select class="fp-d">' + formSelectOptions(1, 31, parsed.d, '日') + '</select></div>' + timeRow + '<div class="form-picker-actions"><button type="button" class="form-picker-cancel">取消</button><button type="button" class="form-picker-confirm">确定</button></div></div>';
  document.body.appendChild(overlay);
  function refreshPreview() {
    var y = parseInt(overlay.querySelector('.fp-y').value, 10);
    var mo = parseInt(overlay.querySelector('.fp-m').value, 10);
    var da = parseInt(overlay.querySelector('.fp-d').value, 10);
    var ho = mode === 'datetime' ? parseInt(overlay.querySelector('.fp-h').value, 10) : 0;
    var mi = mode === 'datetime' ? parseInt(overlay.querySelector('.fp-mi').value, 10) : 0;
    var se = mode === 'datetime' ? parseInt(overlay.querySelector('.fp-s').value, 10) : 0;
    overlay.querySelector('.fp-preview').textContent = formPickerValue(y, mo, da, ho, mi, se, mode);
  }
  overlay.querySelectorAll('select').forEach(function(sel){ sel.addEventListener('change', refreshPreview); });
  overlay.querySelector('.form-picker-cancel').addEventListener('click', function(){ overlay.remove(); });
  overlay.addEventListener('click', function(e){ if (e.target === overlay) overlay.remove(); });
  overlay.querySelector('.form-picker-panel').addEventListener('click', function(e){ e.stopPropagation(); });
  overlay.querySelector('.form-picker-confirm').addEventListener('click', function(){
    var y = parseInt(overlay.querySelector('.fp-y').value, 10);
    var mo = parseInt(overlay.querySelector('.fp-m').value, 10);
    var da = parseInt(overlay.querySelector('.fp-d').value, 10);
    var ho = mode === 'datetime' ? parseInt(overlay.querySelector('.fp-h').value, 10) : 0;
    var mi = mode === 'datetime' ? parseInt(overlay.querySelector('.fp-mi').value, 10) : 0;
    var se = mode === 'datetime' ? parseInt(overlay.querySelector('.fp-s').value, 10) : 0;
    input.value = formPickerValue(y, mo, da, ho, mi, se, mode);
    overlay.remove();
  });
}
function bindFormDatePickers() {
  document.querySelectorAll('.form-date-input, .form-datetime-input').forEach(function(input){
    if (input._pickerBound) return;
    input._pickerBound = true;
    var mode = input.classList.contains('form-datetime-input') ? 'datetime' : 'date';
    input.addEventListener('click', function(e){ e.preventDefault(); showFormDatePicker(input, mode); });
    input.setAttribute('readonly', 'readonly');
  });
}
function validateFormRequired(form) {
  var fields = form.querySelectorAll('input, select, textarea');
  var radioGroups = {};
  for (var i = 0; i < fields.length; i++) {
    var el = fields[i];
    if (!el.required || !el.name) continue;
    if (el.type === 'radio') {
      if (!radioGroups[el.name]) radioGroups[el.name] = { checked: false, el: el };
      if (el.checked) radioGroups[el.name].checked = true;
      continue;
    }
    if (el.type === 'checkbox') continue;
    if (String(el.value || '').trim() !== '') continue;
    var msg = el.getAttribute('data-required-msg') || '请填写此字段';
    showH5Toast(msg);
    el.focus();
    return false;
  }
  var rgKeys = Object.keys(radioGroups);
  for (var j = 0; j < rgKeys.length; j++) {
    var g = radioGroups[rgKeys[j]];
    if (!g.checked) {
      var msg2 = g.el.getAttribute('data-required-msg') || '请选择一项';
      showH5Toast(msg2);
      g.el.focus();
      return false;
    }
  }
  return true;
}
function validateFormFormats(form) {
  var fields = form.querySelectorAll('.form-date-input');
  for (var i = 0; i < fields.length; i++) {
    var v = fields[i].value.trim();
    if (!v) continue;
    if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) { showH5Toast('日期请按 2025-11-11 格式填写'); fields[i].focus(); return false; }
  }
  fields = form.querySelectorAll('.form-datetime-input');
  for (var j = 0; j < fields.length; j++) {
    var dv = fields[j].value.trim();
    if (!dv) continue;
    if (!/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(dv)) { showH5Toast('日期时间请按 2022-11-11 11:11:11 格式填写'); fields[j].focus(); return false; }
  }
  return true;
}
function renderForm(props, cid) {
  var submit = props.submitConfig || {};
  var fields = props.fields || [];
  var tip = submit.showRequiredTip !== false ? '<p class="form-required-tip">* 为必填项</p>' : '';
  var fieldsHtml = fields.map(renderFormField).join('');
  var btnText = formEsc(submit.buttonText || '提交');
  var formId = formEsc(props.formId || '');
  setTimeout(function(){
    if (!props.formId) return;
    fetch(apiBase + '/form/config.php?form_id=' + encodeURIComponent(props.formId)).then(function(r){ return r.json(); }).then(function(j){
      if (!j || j.code !== 0 || !j.data || !j.data.placeholders) return;
      var form = document.getElementById('form-' + props.formId);
      if (!form) return;
      Object.keys(j.data.placeholders).forEach(function(k){
        var el = form.querySelector('[name="' + k + '"]');
        if (el && j.data.placeholders[k]) el.placeholder = j.data.placeholders[k];
      });
    }).catch(function(){});
  }, guideCapture ? 0 : 60);
  return '<div class="component form-box"' + instAttr(cid) + '><h3 class="form-title">' + formEsc(props.formName||'表单') + '</h3>' + tip +
    '<form id="form-' + formId + '" novalidate data-success="' + formEsc(submit.successMessage||'提交成功') + '" data-redirect="' + formEsc(submit.redirectUrl||'') + '">' +
    fieldsHtml + '<button type="submit" class="form-submit-btn">' + btnText + '</button></form></div>';
}
function collectFormData(form) {
  var data = {};
  var checkboxes = {};
  form.querySelectorAll('input, select, textarea').forEach(function(el) {
    if (!el.name) return;
    if (el.type === 'checkbox') {
      if (!checkboxes[el.name]) checkboxes[el.name] = [];
      if (el.checked) checkboxes[el.name].push(el.value);
      return;
    }
    if (el.type === 'radio') { if (el.checked) data[el.name] = el.value; return; }
    data[el.name] = el.value;
  });
  Object.keys(checkboxes).forEach(function(k) { data[k] = checkboxes[k]; });
  return data;
}
function pageComponents(page) {
  if (!page) return [];
  return typeof page.components === 'string' ? JSON.parse(page.components || '[]') : (page.components || []);
}
function findPageStrict(key) {
  return allPages.find(function(p){ return p.page_key === key; }) || null;
}
function findPage(key) {
  return findPageStrict(key) || allPages[0];
}
function findTabBarPage(key) {
  return findPageStrict(key) || findPage(key);
}
function tabIconFile(key, active) {
  var map = { home:'home', category:'category', cart:'cart', mine:'mine' };
  var name = map[key] || 'home';
  return assetUrl('./assets/tab/' + name + (active ? '_active' : '') + '.png');
}
function guideDemoTabBar() {
  var pages = allPages || [];
  var items = [];
  for (var i = 0; i < Math.min(3, pages.length); i++) {
    items.push({ page_key: pages[i].page_key, text: pages[i].page_name || pages[i].page_key });
  }
  if (!items.length) items = [{page_key:'home',text:'首页'},{page_key:'category',text:'分类'},{page_key:'mine',text:'我的'}];
  return { enabled: true, items: items };
}
function renderTabBar(activeKey) {
  var tabBar = globalConfig.tabBar;
  if (guideCapture && window.GUIDE_FORCE_TABBAR && !window.GUIDE_NAV_MAP) {
    if (!tabBar || !tabBar.enabled || !(tabBar.items && tabBar.items.length)) tabBar = guideDemoTabBar();
  }
  if (!tabBar || !tabBar.enabled) return '';
  var primary = (globalConfig.theme && globalConfig.theme.primaryColor) || '#2ecc71';
  var items = tabBar.items || [];
  var tabs = items.map(function(item) {
    var active = item.page_key === activeKey;
    var style = active ? ' style="color:' + primary + '"' : '';
    return '<div class="tab-item' + (active?' active':'') + '" data-key="' + item.page_key + '" data-guide-point="tab:' + item.page_key + '"' + style + '><span class="tab-icon"><img src="' + tabIconFile(item.page_key, active) + '" alt=""></span><span>' + (item.text||'') + '</span></div>';
  }).join('');
  if (guideCapture && window.GUIDE_FORCE_TABBAR) {
    return '<div class="app-tabbar" data-instance-id="guide_tabbar" style="position:absolute;left:0;right:0;bottom:0;width:100%;max-width:430px;height:56px;transform:none;z-index:10;border-top:1px solid #eee;background:#fff">' + tabs + '</div>';
  }
  return '<div class="app-tabbar">' + tabs + '</div>';
}
function bindForms() {
  document.querySelectorAll('form[id^="form-"]').forEach(function(form){
    form.addEventListener('submit', async function(e){
      e.preventDefault();
      if (!validateFormRequired(form)) return;
      if (!validateFormFormats(form)) return;
      var data = collectFormData(form);
      var formId = form.id.replace('form-', '');
      var res = await fetch(apiBase + '/form/' + formId + '/submit.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
      var json = await res.json();
      var okMsg = form.getAttribute('data-success') || '提交成功';
      showH5Toast(json.message || (json.code===0 ? okMsg : '提交失败'));
      if (json.code === 0) {
        var redirect = form.getAttribute('data-redirect') || '';
        if (redirect) { if (redirect.indexOf('#') === 0) location.hash = redirect; else location.href = redirect; }
        else form.reset();
      }
    });
  });
}
function renderGlobalControls() {
  if (guideCapture) return;
  document.querySelectorAll('.gc-float-bar,.gc-splash,.gc-notice').forEach(function(el){ el.remove(); });
  var ctrls = globalConfig.controls || {};
  var html = '';
  if (ctrls.sideHome && ctrls.sideHome.enabled) html += '<a class="gc-float gc-home" href="#home">首页</a>';
  if (ctrls.sideService && ctrls.sideService.enabled) {
    var phone = (ctrls.sideService.phone || '').replace(/\s/g,'');
    html += '<a class="gc-float gc-service" href="tel:' + phone + '">客服</a>';
  }
  if (html) {
    var bar = document.createElement('div');
    bar.className = 'gc-float-bar';
    bar.innerHTML = html;
    document.body.appendChild(bar);
    bar.querySelectorAll('a[href^="#"]').forEach(function(a){
      a.addEventListener('click', function(e){ e.preventDefault(); navTo(a.getAttribute('href').replace('#','')); });
    });
  }
  if (ctrls.splashPopup && ctrls.splashPopup.enabled && !sessionStorage.getItem('splash_done')) {
    var img = ctrls.splashPopup.image || '';
    var link = ctrls.splashPopup.link || '';
    var overlay = document.createElement('div');
    overlay.className = 'gc-splash';
    overlay.innerHTML = '<div class="gc-splash-box">' + (img ? '<img src="' + img + '" alt="">' : '<p>开屏弹窗</p>') + '<button type="button">关闭</button></div>';
    document.body.appendChild(overlay);
    overlay.querySelector('button').addEventListener('click', function(){ sessionStorage.setItem('splash_done','1'); overlay.remove(); });
    var splashImg = overlay.querySelector('img');
    if (splashImg && link) { splashImg.style.cursor = 'pointer'; splashImg.addEventListener('click', function(){ location.href = link; sessionStorage.setItem('splash_done','1'); overlay.remove(); }); }
  }
  if (ctrls.noticePopup && ctrls.noticePopup.enabled) {
    var np = ctrls.noticePopup;
    var nTitle = np.title || '通知';
    var noticeKey = 'notice_popup_' + nTitle;
    var freq = np.frequency || 'session';
    var shouldShow = true;
    if (freq === 'session') shouldShow = !sessionStorage.getItem(noticeKey);
    else if (freq === 'once') shouldShow = !localStorage.getItem(noticeKey);
    else if (freq === 'daily') {
      var lastDay = localStorage.getItem(noticeKey);
      shouldShow = lastDay !== new Date().toDateString();
    }
    if (shouldShow) {
    var nImg = np.image || '';
    var nContent = np.content || '';
    var nLinkObj = np.linkConfig || np.link || '';
    var nLink = typeof nLinkObj === 'string' ? nLinkObj : resolveLinkHref(nLinkObj);
    var notice = document.createElement('div');
    notice.className = 'gc-notice';
    notice.innerHTML = '<div class="gc-notice-box"><h3>' + escHtml(nTitle) + '</h3>' + (nImg ? '<img src="' + nImg + '" alt="">' : '') + '<p>' + escHtml(nContent) + '</p><button type="button">我知道了</button></div>';
    document.body.appendChild(notice);
    notice.querySelector('button').addEventListener('click', function(){
      if (freq === 'session') sessionStorage.setItem(noticeKey,'1');
      else if (freq === 'daily') localStorage.setItem(noticeKey, new Date().toDateString());
      else localStorage.setItem(noticeKey,'1');
      notice.remove();
    });
    if (nLink) {
      var clickTarget = notice.querySelector('img') || notice.querySelector('.gc-notice-box');
      if (clickTarget) {
        clickTarget.style.cursor='pointer';
        clickTarget.addEventListener('click', function(e){
          if (e.target && e.target.tagName === 'BUTTON') return;
          if (nLink.indexOf('#')===0) navByHref(nLink, e);
          else location.href=nLink;
          if (freq === 'session') sessionStorage.setItem(noticeKey,'1');
          else if (freq === 'daily') localStorage.setItem(noticeKey, new Date().toDateString());
          else localStorage.setItem(noticeKey,'1');
          notice.remove();
        });
      }
    }
    }
  }
}
function renderSubPageFromDB(path) {
  if (path === 'order-list' && window.HAS_COMMERCE) return null;
  if (path === 'order' && window.HAS_COMMERCE) return null;
  if (path === 'order-detail' && window.HAS_COMMERCE) return null;
  var pg = findPageStrict(path);
  if (!pg) return null;
  var comps = pageComponents(pg);
  if (!comps.length) return null;
  var inner = renderPageComponents(pg);
  return renderSubPageNav(pg.page_name || path) + inner;
}
function renderApp(pageKey, queryOverride) {
  applyTheme();
  var route = parseRoute();
  var path = pageKey || route.path || 'home';
  if (pageKey && pageKey.indexOf('?') < 0 && !isSubPage(pageKey)) path = pageKey;
  var query = queryOverride || route.query;
  window.__routeQuery = query || {};
  var pageHtml;
  var subPageDb = null;
  if (isTabBarPage(path)) {
    if (path === 'cart' && window.HAS_COMMERCE) {
      pageHtml = renderCartPage();
    } else if (path === 'order' && window.HAS_COMMERCE) {
      pageHtml = renderOrderPage(query);
    } else {
      var tabPage = findTabBarPage(path);
      pageHtml = renderPageComponents(tabPage);
    }
  } else {
    subPageDb = isSubPage(path) ? renderSubPageFromDB(path) : null;
    pageHtml = subPageDb || (isSubPage(path) ? renderVirtualPage(path, query) : (function(){
      var page = findPage(path);
      return renderPageComponents(page);
    })());
  }
  var pageCls = 'page';
  if (isTabBarPage(path)) {
    var pgTab = findTabBarPage(path);
    var compsTab = pageComponents(pgTab);
    if (compsTab.length && compsTab[0].type === 'pageHeader') pageCls = 'page page-flush-top';
  } else if (subPageDb) {
    var pgSub = findPage(path);
    var compsSub = pageComponents(pgSub);
    if (compsSub.length && compsSub[0].type === 'pageHeader') pageCls = 'page page-flush-top';
  } else if (!isSubPage(path)) {
    var pg = findPage(path);
    var comps = pageComponents(pg);
    if (comps.length && comps[0].type === 'pageHeader') pageCls = 'page page-flush-top';
  }
  var hasTab = globalConfig.tabBar && globalConfig.tabBar.enabled;
  if (guideCapture && window.GUIDE_FORCE_TABBAR && !window.GUIDE_NAV_MAP) hasTab = true;
  app.innerHTML = '<div class="app-shell' + (hasTab?' has-tab':'') + '"><div class="' + pageCls + '">' + pageHtml + '</div></div>' + (hasTab ? renderTabBar(path) : '');
  if (guideCapture && window.GUIDE_FORCE_TABBAR) {
    app.style.height = 'auto';
    app.style.minHeight = '0';
    app.style.position = 'relative';
    app.style.overflow = 'visible';
    app.style.maxWidth = '430px';
    app.style.margin = '0 auto';
    app.style.paddingBottom = '56px';
    var shell = app.querySelector('.app-shell');
    if (shell) shell.style.minHeight = '0';
  }
  initSwipers();
  bindForms();
  bindFormDatePickers();
  renderGlobalControls();
  document.querySelectorAll('.tab-item').forEach(function(el){
    el.addEventListener('click', function(){ navTo(el.dataset.key); });
  });
  if (guideCapture) {
    var delay = (window.GUIDE_FORCE_TABBAR ? 600 : 120);
    setTimeout(function(){ window.__GUIDE_READY__ = true; }, delay);
  }
}
if (guideCapture) { applyTheme(); renderApp(window.GUIDE_PAGE_KEY || 'home'); if (window.GUIDE_ROUTE) { setTimeout(function(){ navTo(window.GUIDE_ROUTE); }, 120); } }
else { renderApp(parseRoute().path); window.addEventListener('hashchange', function(){ renderApp(parseRoute().path); }); }

function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); }
function widgetIsLoggedIn(){ return !!(window.__USER_LOGGED_IN__ || window.__userId); }
function wgBoxId(prefix,cid){ return 'wg-'+prefix+'-'+(cid||Math.random().toString(36).slice(2)); }
function wgMount(boxId,cid,fallback,paint){
  if(guideCapture||!cid) return;
  setTimeout(function(){
    fetch(apiBase+'/widget/get.php?id='+encodeURIComponent(cid)).then(function(r){return r.json();}).then(function(j){
      if(j&&j.code===0&&j.data&&j.data.props){ var el=document.getElementById(boxId); if(el) paint(el,Object.assign({},fallback,j.data.props)); }
    }).catch(function(){});
  },30);
}
function renderContainer(props,cid){
  var boxId=wgBoxId('ct',cid);
  var build=function(p){
    var children=p.children||[];
    var inner=children.map(function(ch){ return renderComponent({type:ch.type,props:ch.props||{},id:ch.id||''}); }).join('');
    var st='padding:'+(p.padding||12)+'px;background:'+(p.bgColor||'#fff')+';border-radius:'+(p.borderRadius||8)+'px;';
    if(p.shadow!==false) st+='box-shadow:0 2px 12px rgba(0,0,0,.08);';
    if(p.overlap) st+='margin-top:-'+p.overlap+'px;position:relative;z-index:2;';
    return inner||'<div style="color:#ccc;font-size:12px;text-align:center">容器</div>';
  };
  wgMount(boxId,cid,props,function(el,p){ el.style.cssText=buildStyle(p); el.innerHTML=build(p); });
  function buildStyle(p){
    var st='padding:'+(p.padding||12)+'px;background:'+(p.bgColor||'#fff')+';border-radius:'+(p.borderRadius||8)+'px;';
    if(p.shadow!==false) st+='box-shadow:0 2px 12px rgba(0,0,0,.08);';
    if(p.overlap) st+='margin-top:-'+p.overlap+'px;position:relative;z-index:2;';
    return st;
  }
  return '<div class="component container-box" id="'+boxId+'" style="'+buildStyle(props)+'"'+instAttr(cid)+'>'+build(props)+'</div>';
}
function tabNavClick(e, idx, boxId, href){
  var box=document.getElementById(boxId);
  if(!box) return true;
  var ac=box.getAttribute('data-active-color')||'#e74c3c';
  box.querySelectorAll('.tab-nav-item').forEach(function(el,i){
    el.classList.toggle('active', i===idx);
    el.style.color=i===idx?ac:'';
  });
  if(href && href.indexOf('#')===0){
    if(e) e.preventDefault();
    navByHref(href,e);
    return false;
  }
  return true;
}
function innerTabNav(props, boxId){
  var items=props.items||[], ai=props.activeIndex||0;
  var ac=props.activeColor||'#e74c3c';
  var bid=boxId||'';
  return items.map(function(it,i){
    var href=resolveLinkHref(it.link);
    var cls='tab-nav-item'+(i===ai?' active':'');
    var st=i===ai?'color:'+ac+';':'';
    if(href.indexOf('#')===0) return '<a class="'+cls+'" style="'+st+'" href="'+href+'" onclick="return tabNavClick(event,'+i+',\''+bid+'\',\''+href+'\')">'+escHtml(it.text)+'</a>';
    if(href) return '<a class="'+cls+'" style="'+st+'" href="'+href+'" target="_blank" rel="noopener noreferrer" onclick="tabNavClick(event,'+i+',\''+bid+'\',\'\');return true;">'+escHtml(it.text)+'</a>';
    return '<span class="'+cls+'" style="'+st+';cursor:pointer" onclick="tabNavClick(event,'+i+',\''+bid+'\',\'\')">'+escHtml(it.text)+'</span>';
  }).join('');
}
function renderTabNav(props,cid){
  var boxId=wgBoxId('tab',cid);
  var ac=props.activeColor||'#e74c3c';
  wgMount(boxId,cid,props,function(el,p){ el.className='component tab-nav style-'+(p.style||'underline'); el.setAttribute('data-active-color', p.activeColor||'#e74c3c'); el.innerHTML=innerTabNav(p, boxId); });
  return '<div class="component tab-nav style-'+(props.style||'underline')+'" id="'+boxId+'" data-active-color="'+ac+'"'+instAttr(cid)+'>'+innerTabNav(props, boxId)+'</div>';
}
function innerFilterBar(props){
  var dds=(props.dropdowns||[]).map(function(d){ return '<span class="filter-dd">'+escHtml(d.text)+' ▾</span>'; }).join('');
  var tags=(props.tags||[]).map(function(t,i){
    return '<span class="filter-tag'+(i===(props.activeTagIndex||0)?' active':'')+'">'+escHtml(t)+'</span>';
  }).join('');
  return '<div class="filter-dropdowns">'+dds+'</div><div class="filter-tags">'+tags+'</div>';
}
function renderFilterBar(props,cid){
  var boxId=wgBoxId('fb',cid);
  wgMount(boxId,cid,props,function(el,p){ el.innerHTML=innerFilterBar(p); });
  return '<div class="component filter-bar" id="'+boxId+'"'+instAttr(cid)+'>'+innerFilterBar(props)+'</div>';
}
function innerPromoGrid(props){
  var cols=props.columns||2, items=props.items||[];
  return items.map(function(it){
    var img=it.image?'<img src="'+imgThumbUrl(it.image)+'" alt="">':'';
    var badge=it.badge?'<span class="pg-badge">'+escHtml(it.badge)+'</span>':'';
    var href=resolveLinkHref(it.link);
    var inner=badge+img+'<div class="pg-title">'+escHtml(it.title)+'</div><div class="pg-sub">'+escHtml(it.subtitle)+'</div>';
    if(href.indexOf('#')===0) return '<a class="promo-grid-cell" style="background:'+(it.bgColor||'#f5f5f5')+';text-decoration:none;color:inherit" href="'+href+'" onclick="navByHref(\''+href+'\',event)">'+inner+'</a>';
    return '<div class="promo-grid-cell" style="background:'+(it.bgColor||'#f5f5f5')+'">'+inner+'</div>';
  }).join('');
}
function renderPromoGrid(props,cid){
  var boxId=wgBoxId('pg',cid);
  wgMount(boxId,cid,props,function(el,p){ el.style.gridTemplateColumns='repeat('+(p.columns||2)+',1fr)'; el.innerHTML=innerPromoGrid(p); });
  return '<div class="component promo-grid" id="'+boxId+'" style="grid-template-columns:repeat('+(props.columns||2)+',1fr)"'+instAttr(cid)+'>'+innerPromoGrid(props)+'</div>';
}
function innerVideo(props){
  var h=Math.round((props.height||400)/2);
  var poster=props.poster||'';
  var src=props.src||'';
  if(src) return '<video src="'+src+'" poster="'+poster+'" controls playsinline style="height:'+h+'px"></video>';
  var bg=poster?'background:url('+poster+') center/cover':'background:#333';
  return '<div class="video-box" style="height:'+h+'px;'+bg+'"><div class="video-play">▶</div><div class="video-title">'+escHtml(props.title||'观看视频')+'</div></div>';
}
function renderVideo(props,cid){
  var boxId=wgBoxId('vd',cid);
  wgMount(boxId,cid,props,function(el,p){ el.innerHTML=innerVideo(p); });
  var h=Math.round((props.height||400)/2);
  return '<div class="component video-box" id="'+boxId+'" style="height:'+h+'px"'+instAttr(cid)+'>'+innerVideo(props)+'</div>';
}
function renderRateStars(score,max,clickable){
  max=max||5; score=parseFloat(score)||0;
  var h=''; for(var i=1;i<=max;i++){
    var cls='rate-star'+(i<=Math.round(score)?'':' empty');
    if(clickable) h+='<span class="'+cls+'" data-star="'+i+'" style="cursor:pointer">★</span>';
    else h+='<span class="'+cls+'">★</span>';
  }
  return h;
}
function buildRateHtml(p,clickable){
  var cnt=p.showCount!==false?'<span class="rate-count">'+(p.count||0)+'条评价</span>':'';
  var can=clickable!==false&&p.allowUserRate!==false;
  return renderRateStars(p.score,p.maxScore,can)+'<span class="rate-score-num" style="margin-left:4px;font-weight:600">'+(p.score||0)+'</span>'+cnt;
}
function bindRateBox(root,cid,props){
  if(props.allowUserRate===false) return;
  var stars=root.querySelectorAll('.rate-star[data-star]');
  if(!stars.length) return;
  stars.forEach(function(st){
    st.onclick=function(){
      var val=parseInt(st.getAttribute('data-star'),10)||0;
      if(!val) return;
      var vk=localStorage.getItem('visitor_key')||('v_'+Date.now());
      localStorage.setItem('visitor_key',vk);
      var cnt=parseInt(props.count,10)||0;
      var total=parseFloat(props.totalScore)||0;
      var nextScore=cnt>0?((total+val)/(cnt+1)):val;
      var optimistic=Object.assign({},props,{score:nextScore,count:cnt+1});
      root.innerHTML=buildRateHtml(optimistic,true);
      bindRateBox(root,cid,optimistic);
      fetch(apiBase+'/rate/submit.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({instance_id:cid,score:val,visitor_key:vk})})
        .then(function(r){return r.json();}).then(function(j){
          if(j&&j.code===0&&j.data){
            var p=Object.assign({},props,j.data);
            root.innerHTML=buildRateHtml(p,true);
            bindRateBox(root,cid,p);
          } else showH5Toast((j&&j.message)||'评分失败');
        }).catch(function(){ showH5Toast('网络错误'); bindRateBox(root,cid,props); });
    };
  });
}
function renderRate(props,cid){
  var boxId=wgBoxId('rt',cid);
  var paint=function(el,p){
    el.innerHTML=buildRateHtml(p,true);
    bindRateBox(el,cid,p);
  };
  wgMount(boxId,cid,props,function(el,p){ paint(el,p); });
  setTimeout(function(){ var el=document.getElementById(boxId); if(el) paint(el,props); },50);
  return '<div class="component rate-box" id="'+boxId+'"'+instAttr(cid)+'>'+buildRateHtml(props,true)+'</div>';
}
function innerServiceCard(props){
  var title=props.title?'<div class="product-section-head"><strong>'+escHtml(props.title)+'</strong></div>':'';
  var cards=(props.items||[]).map(function(it){
    var logoSrc=it.logo||it.image||'';
    var logo=logoSrc?'<img class="sc-logo" src="'+imgThumbUrl(logoSrc)+'" alt="">':'<div class="sc-logo"></div>';
    var tags=(it.tags||[]).map(function(t){ return '<span class="sc-tag">'+escHtml(t)+'</span>'; }).join('');
    var imgsArr=Array.isArray(it.images)?it.images.slice(0,3):[];
    if(!imgsArr.length&&it.image) imgsArr=[it.image];
    while(imgsArr.length<3) imgsArr.push('');
    var imgs=imgsArr.map(function(u){ return u?'<img src="'+imgThumbUrl(u)+'" alt="">':'<div style="width:72px;height:72px;background:#f0f0f0;border-radius:6px"></div>'; }).join('');
    var meta=escHtml(it.distance||'')+' · '+escHtml(it.status||'')+' · '+renderRateStars(it.rating,5)+' '+it.rating;
    return '<div class="service-card"><div class="sc-head">'+logo+'<div class="sc-info"><div class="sc-name">'+escHtml(it.name)+'</div><div class="sc-meta">'+meta+' · '+it.ratingCount+'评</div><div class="sc-tags">'+tags+'</div></div></div><div class="sc-imgs">'+imgs+'</div></div>';
  }).join('');
  return title+cards;
}
function renderServiceCard(props,cid){
  var boxId=wgBoxId('sc',cid);
  var build=function(p){
    var inner=innerServiceCard(p);
    var href=resolveLinkHref(p.link);
    if(href.indexOf('#')===0) return '<a class="service-card-link-wrap" href="'+href+'" onclick="navByHref(\''+href+'\',event)" style="display:block;text-decoration:none;color:inherit">'+inner+'</a>';
    if(href) return '<a class="service-card-link-wrap" href="'+href+'" target="_blank" rel="noopener" style="display:block;text-decoration:none;color:inherit">'+inner+'</a>';
    return inner;
  };
  wgMount(boxId,cid,props,function(el,p){ el.innerHTML=build(p); });
  return '<div class="component service-card-box" id="'+boxId+'"'+instAttr(cid)+'>'+build(props)+'</div>';
}
function innerListMenu(props){
  var arrow=props.showArrow!==false?'<span class="lm-arrow">›</span>':'';
  return (props.items||[]).map(function(it){
    var href=resolveLinkHref(it.link);
    var inner='<span class="lm-icon">'+escHtml(it.icon||'•')+'</span><span class="lm-text">'+escHtml(it.text)+'</span><span class="lm-value">'+escHtml(it.value||'')+'</span>'+arrow;
    if(href.indexOf('#')===0) return '<a class="list-menu-item" href="'+href+'" onclick="navByHref(\''+href+'\',event)">'+inner+'</a>';
    return '<div class="list-menu-item">'+inner+'</div>';
  }).join('');
}
function renderListMenu(props,cid){
  var boxId=wgBoxId('lm',cid);
  wgMount(boxId,cid,props,function(el,p){ el.innerHTML=innerListMenu(p); });
  return '<div class="component list-menu" id="'+boxId+'"'+instAttr(cid)+'>'+innerListMenu(props)+'</div>';
}
function innerStatsRow(props){
  var cols=props.columns||3;
  return (props.items||[]).slice(0,cols).map(function(it){
    return '<div class="stats-cell"><div class="stats-value">'+escHtml(it.value)+'<span class="stats-unit">'+escHtml(it.unit||'')+'</span></div><div class="stats-label">'+escHtml(it.label)+'</div></div>';
  }).join('');
}
function renderStatsRow(props,cid){
  var boxId=wgBoxId('sr',cid);
  wgMount(boxId,cid,props,function(el,p){ el.innerHTML=innerStatsRow(p); });
  return '<div class="component stats-row" id="'+boxId+'"'+instAttr(cid)+'>'+innerStatsRow(props)+'</div>';
}
function renderWalletCard(props,cid){
  var boxId=wgBoxId('wc',cid);
  var build=function(p){
    var href=resolveLinkHref(p.link);
    var btn='<button class="wallet-btn" type="button">'+escHtml(p.rechargeText||'充值')+'</button>';
    if(href.indexOf('#')===0) btn='<a class="wallet-btn" href="'+href+'" onclick="navByHref(\''+href+'\',event)">'+escHtml(p.rechargeText||'充值')+'</a>';
    return '<div><div class="wallet-user">'+escHtml(p.userName)+' ›</div><div class="wallet-balance">'+(p.currency||'¥')+' '+escHtml(p.balance)+'</div></div>'+btn;
  };
  wgMount(boxId,cid,props,function(el,p){ el.style.background=p.bgColor||'#fff'; el.innerHTML=build(p); });
  return '<div class="component wallet-card" id="'+boxId+'" style="background:'+(props.bgColor||'#fff')+'"'+instAttr(cid)+'>'+build(props)+'</div>';
}
function renderFloatingButton(props,cid){
  var pos=props.position==='bottom-left'?'pos-bl':'pos-br';
  var href=resolveLinkHref(props.link);
  var bottom=parseInt(props.bottom,10); if(isNaN(bottom)) bottom=130;
  var st='background:'+(props.bgColor||'#e74c3c')+';color:'+(props.textColor||'#fff')+';bottom:'+bottom+'px;';
  if(href.indexOf('#')===0) return '<a class="floating-btn '+pos+'" style="'+st+'" href="'+href+'" onclick="navByHref(\''+href+'\',event)"'+instAttr(cid)+'>'+escHtml(props.text)+'</a>';
  return '<span class="floating-btn '+pos+'" style="'+st+'"'+instAttr(cid)+'>'+escHtml(props.text)+'</span>';
}
function renderServiceFloat(props,cid){
  var pos=props.position==='bottom-left'?'pos-bl':'pos-br';
  var phone=props.phone||'';
  var bottom=parseInt(props.bottom,10); if(isNaN(bottom)) bottom=72;
  return '<a class="service-float '+pos+'" style="background:'+(props.bgColor||'#2ecc71')+';bottom:'+bottom+'px;" href="tel:'+phone+'"'+instAttr(cid)+'>'+escHtml(props.text||'客服')+'</a>';
}
function innerWaterfall(props){
  return (props.items||[]).map(function(it){
    var img=it.image?'<img src="'+imgThumbUrl(it.image)+'" style="height:'+(it.height||180)+'px" alt="">':'<div style="height:'+(it.height||180)+'px;background:#eee"></div>';
    var vid=it.isVideo?'<span class="wf-video">▶</span>':'';
    return '<div class="waterfall-item">'+vid+img+'<div class="wf-title">'+escHtml(it.title)+'</div></div>';
  }).join('');
}
function renderWaterfall(props,cid){
  var boxId=wgBoxId('wf',cid);
  wgMount(boxId,cid,props,function(el,p){ el.style.columnCount=(p.columns||2); el.innerHTML=innerWaterfall(p); });
  return '<div class="component waterfall" id="'+boxId+'" style="column-count:'+(props.columns||2)+'"'+instAttr(cid)+'>'+innerWaterfall(props)+'</div>';
}
function innerFeatureCard(props){
  return (props.items||[]).map(function(it){
    var img=it.image?'<img src="'+imgThumbUrl(it.image)+'" alt="">':'<div style="width:120px;height:100px;background:#eee"></div>';
    var inner='<div class="feature-card" style="background:'+(it.bgColor||'#f5f5f5')+'"><div class="fc-left"><div class="fc-title">'+escHtml(it.title)+'</div><div class="fc-sub">'+escHtml(it.subtitle)+'</div><span class="fc-banner">'+escHtml(it.bannerText||'')+'</span></div><div class="fc-right">'+img+'</div></div>';
    var href=resolveLinkHref(it.link);
    if(href.indexOf('#')===0) return '<a class="feature-card-link" href="'+href+'" onclick="navByHref(\''+href+'\',event)">'+inner+'</a>';
    if(href.indexOf('http')===0) return '<a class="feature-card-link" href="'+href+'" target="_blank" rel="noopener">'+inner+'</a>';
    return inner;
  }).join('');
}
function renderFeatureCard(props,cid){
  var boxId=wgBoxId('fc',cid);
  wgMount(boxId,cid,props,function(el,p){ el.innerHTML=innerFeatureCard(p); });
  return '<div class="component feature-card-box" id="'+boxId+'"'+instAttr(cid)+'>'+innerFeatureCard(props)+'</div>';
}
function refreshLoginBanner(boxId){
  if(typeof guideCapture!=='undefined'&&guideCapture) return;
  fetch(apiBase+'/user/me.php',{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){
    var el=document.getElementById(boxId);
    if(!el) return;
    if(j&&j.code===0&&j.data&&j.data.logged_in) el.style.display='none';
    else el.style.display='';
  }).catch(function(){});
}
function renderLoginBanner(props,cid){
  var boxId=wgBoxId('lb',cid);
  var build=function(p){
    var href=resolveLinkHref(p.link);
    var btn='<button class="login-banner-btn" type="button">'+escHtml(p.buttonText||'立即登录')+'</button>';
    if(href.indexOf('#')===0) btn='<a class="login-banner-btn" href="'+href+'" onclick="navByHref(\''+href+'\',event)">'+escHtml(p.buttonText||'立即登录')+'</a>';
    return '<span>'+escHtml(p.text)+'</span>'+btn;
  };
  wgMount(boxId,cid,props,function(el,p){ el.style.background=p.bgColor||'rgba(0,0,0,.75)'; el.innerHTML=build(p); refreshLoginBanner(boxId); });
  setTimeout(function(){ refreshLoginBanner(boxId); }, 80);
  return '<div class="component login-banner" id="'+boxId+'" style="background:'+(props.bgColor||'rgba(0,0,0,.75)')+'"'+instAttr(cid)+'>'+build(props)+'</div>';
}
function deoverlapFloatingWidgets(){
  var gap=58, size=52;
  function sideOf(n){
    if(n.classList.contains('pos-bl')) return 'bl';
    if(n.classList.contains('pos-br')) return 'br';
    return 'br';
  }
  function applyGroup(sel){
    var nodes=[];
    document.querySelectorAll(sel).forEach(function(n){ nodes.push(n); });
    if(nodes.length<2) return;
    var groups={bl:[],br:[]};
    nodes.forEach(function(n){ groups[sideOf(n)].push(n); });
    ['bl','br'].forEach(function(k){
      var list=groups[k]; if(list.length<2) return;
      list.sort(function(a,b){
        return (parseInt(a.style.bottom||'0',10)||0)-(parseInt(b.style.bottom||'0',10)||0);
      });
      for(var i=1;i<list.length;i++){
        var prev=parseInt(list[i-1].style.bottom||'0',10)||0;
        var cur=parseInt(list[i].style.bottom||'0',10)||0;
        if(cur<prev+gap) list[i].style.bottom=(prev+gap)+'px';
      }
    });
  }
  applyGroup('.floating-btn');
  applyGroup('.service-float');
  applyGroup('.sf-component');
  var bar=document.querySelector('.gc-float-bar');
  if(bar){
    var items=bar.querySelectorAll('.gc-float');
    if(items.length>1){
      for(var j=1;j<items.length;j++){
        var p=items[j-1], c=items[j];
        var pb=parseInt(p.style.marginBottom||'0',10)||0;
        if(pb<8) c.style.marginTop='8px';
      }
    }
  }
}
(function(){
  var _ra=renderApp;
  renderApp=function(a,b){ _ra(a,b); setTimeout(deoverlapFloatingWidgets, 60); document.querySelectorAll('.login-banner[id]').forEach(function(el){ refreshLoginBanner(el.id); }); };
})();
function renderLocationPicker(props,cid){
  var boxId=wgBoxId('lp',cid);
  var build=function(p){
    var cities=p.cities||[];
    var opts=cities.map(function(c){ return '<option'+(c===p.defaultCity?' selected':'')+'>'+escHtml(c)+'</option>'; }).join('');
    var icon=p.showMapIcon!==false?'📍 ':'';
    return icon+'<select>'+opts+'</select>';
  };
  wgMount(boxId,cid,props,function(el,p){ el.innerHTML=build(p); });
  return '<div class="component location-picker" id="'+boxId+'"'+instAttr(cid)+'>'+build(props)+'</div>';
}
function renderMarketingEntry(props,cid,type){
  var boxId=wgBoxId('me',cid);
  var build=function(p){
    var cover=p.image||p.cover||'';
    var img=cover?'<img class="me-cover" src="'+imgThumbUrl(cover)+'" alt="">':'';
    var extra=type==='liveEntry'&&p.liveTag?'<span class="me-live">'+escHtml(p.liveTag)+'</span>':'';
    var cd=type==='flashSale'&&p.showCountdown?'<div class="me-countdown" style="font-size:11px;color:#e74c3c;margin-top:4px">'+(typeof formatCountdown==='function'?formatCountdown(p.countdownEnd):'00:00:00')+'</div>':'';
    return '<div class="me-info"><div class="me-title">'+escHtml(p.title)+extra+'</div><div class="me-sub">'+escHtml(p.subtitle)+'</div>'+cd+'</div>'+img;
  };
  wgMount(boxId,cid,props,function(el,p){ el.style.background=p.bgColor||'#f5f5f5'; el.innerHTML=build(p); });
  var href=resolveLinkHref(props.link);
  if((type==='groupBuy'||type==='flashSale')&&cid&&(href.indexOf('#group-buy')===0||href.indexOf('#flash-sale')===0)){
    var sep=href.indexOf('?')>=0?'&':'?';
    href=href+sep+'entry='+encodeURIComponent(cid);
  }
  var inner=build(props);
  if(href.indexOf('#')===0) return '<a class="component marketing-entry" id="'+boxId+'" style="background:'+(props.bgColor||'#f5f5f5')+';text-decoration:none;color:inherit" href="'+href+'" onclick="navByHref(\''+href+'\',event)"'+instAttr(cid)+'>'+inner+'</a>';
  return '<div class="component marketing-entry" id="'+boxId+'" style="background:'+(props.bgColor||'#f5f5f5')+'"'+instAttr(cid)+'>'+inner+'</div>';
}
function renderCheckIn(props,cid){
  var boxId=wgBoxId('ci',cid);
  var build=function(p){
    var days=p.days||7, checked=p.checkedDays||0;
    var banner=p.image?'<img src="'+imgThumbUrl(p.image)+'" style="width:100%;max-height:120px;object-fit:cover;border-radius:8px;margin-bottom:8px" alt="">':'';
    var cells=''; for(var i=1;i<=days;i++){ cells+='<span class="checkin-day'+(i<=checked?' done':'')+'">'+i+'</span>'; }
    var href=resolveLinkHref(p.link);
    var btn='<button type="button" style="padding:8px 24px;border:none;border-radius:20px;background:var(--primary-color,#2ecc71);color:#fff">签到</button>';
    if(href.indexOf('#')===0) btn='<a href="'+href+'" onclick="navByHref(\''+href+'\',event)" style="padding:8px 24px;border-radius:20px;background:var(--primary-color,#2ecc71);color:#fff;text-decoration:none;display:inline-block">签到</a>';
    return banner+'<div class="me-title">'+escHtml(p.title)+'</div><div class="me-sub">'+escHtml(p.subtitle)+'</div><div class="checkin-days">'+cells+'</div>'+btn;
  };
  wgMount(boxId,cid,props,function(el,p){ el.innerHTML=build(p); });
  return '<div class="component checkin-box" id="'+boxId+'"'+instAttr(cid)+'>'+build(props)+'</div>';
}
function renderVirtualMarketingPage(title,desc){
  return renderSubPageNav(title)+'<div class="virtual-page"><div class="vp-title">'+escHtml(title)+'</div><div class="virtual-card"><p>'+escHtml(desc)+'</p><p style="color:#999;font-size:13px">功能占位页，可在后台配置后扩展</p></div></div>';
}
function renderGroupBuyPage(){
  var pg = findPageStrict('group-buy');
  if (!pg) return renderVirtualMarketingPage('拼团专区','邀请好友一起拼，享更低价格');
  var comps = pageComponents(pg).filter(function(c){ return c.visible !== false; });
  return renderSubPageNav('拼团专区') + renderPageComponents({ page_key: 'group-buy', components: comps });
}
function renderFlashSalePage(){
  var pg = findPageStrict('flash-sale');
  if (!pg) return renderSubPageNav('限时秒杀')+'<div class="virtual-page"><div class="article-empty">请先在画布配置秒杀子页</div></div>';
  var comps = pageComponents(pg).filter(function(c){ return c.visible !== false; });
  return renderSubPageNav('限时秒杀') + renderPageComponents({ page_key: 'flash-sale', components: comps });
}
function renderLiveRoomPage(){ return renderVirtualMarketingPage('直播间','精彩直播进行中'); }
function renderCheckInPage(){ return renderVirtualMarketingPage('每日签到','连续签到领取奖励'); }
function titleBarTitleStyle(p){
  var s='';
  if(p.titleColor||p.color) s+='color:'+(p.titleColor||p.color)+';';
  if(p.titleFontSize) s+='font-size:'+p.titleFontSize+'px;';
  if(p.titleFontWeight) s+='font-weight:'+p.titleFontWeight+';';
  return s;
}
function titleBarSubtitleStyle(p){
  var s='';
  if(p.subtitleColor) s+='color:'+p.subtitleColor+';';
  if(p.subtitleFontSize) s+='font-size:'+p.subtitleFontSize+'px;';
  return s;
}
function renderTitleBarUpgraded(props,cid){
  var boxId=wgBoxId('tb',cid);
  var build=function(p){
    var more=p.showMoreLink&&p.moreText?'<a class="tb-more" href="#">'+escHtml(p.moreText||'查看更多 ›')+'</a>':'';
    return '<h2 style="'+titleBarTitleStyle(p)+'">'+escHtml(p.title||'')+more+'</h2><p style="'+titleBarSubtitleStyle(p)+'">'+escHtml(p.subtitle||'')+'</p>';
  };
  wgMount(boxId,cid,props,function(el,p){
    var skin=globalConfig.moduleSkin||'default';
    el.className='component title-bar skin-'+skin+' align-'+(p.align||'left');
    el.innerHTML=build(p);
  });
  var skin=globalConfig.moduleSkin||'default';
  var align=props.align||'left';
  return '<div class="component title-bar skin-'+skin+' align-'+align+'" id="'+boxId+'"'+instAttr(cid)+'>'+build(props)+'</div>';
}
function renderSearchBarUpgraded(props,cid){
  var boxId=wgBoxId('sb',cid);
  var build=function(p){
    var left=p.showScan?'<span class="sb-left">⌁</span>':'';
    var right='';
    if(p.showVoice) right+='<span class="sb-right">🎤</span>';
    if(p.showMessage) right+='<span class="sb-right">💬</span>';
    return left+'<input placeholder="'+escHtml(p.placeholder||'搜索')+'" readonly/>'+right;
  };
  wgMount(boxId,cid,props,function(el,p){
    var fl=p.style==='float'?' style-float':'';
    el.className='component search-bar-ext'+fl;
    el.style.background=p.bgColor||'#fff';
    el.innerHTML=build(p);
  });
  var fl=props.style==='float'?' style-float':'';
  return '<div class="component search-bar-ext'+fl+'" id="'+boxId+'" style="background:'+(props.bgColor||'#fff')+'"'+instAttr(cid)+'>'+build(props)+'</div>';
}
function renderMessageField(f){
  var key=formEsc(f.key||''), label=formEsc(f.label||''), ph=formEsc(f.placeholder||'');
  if(f.type==='textarea') {
    var rows=Math.max(2, parseInt(f.rows,10)||3);
    return '<div class="mb-field"><label>'+label+'</label><textarea name="'+key+'" rows="'+rows+'" style="min-height:'+(rows*22)+'px" placeholder="'+ph+'"></textarea></div>';
  }
  var tp=f.type==='phone'?'tel':(f.type||'text');
  return '<div class="mb-field"><label>'+label+'</label><input type="'+tp+'" name="'+key+'" placeholder="'+ph+'"></div>';
}
function buildMessageBoardHtml(props,cid){
  var fields=(props.fields||[]).map(renderMessageField).join('');
  return '<div class="mb-title">'+escHtml(props.title||'在线留言')+'</div><form class="mb-form" data-instance="'+formEsc(cid||'')+'">'+fields+'<button type="submit" class="mb-submit">'+escHtml(props.submitText||'提交留言')+'</button></form><div class="mb-tip">留言仅管理员可见</div>';
}
function bindMessageBoard(root,cid,props){
  var form=root.querySelector('.mb-form'); if(!form) return;
  form.setAttribute('novalidate','novalidate');
  form.onsubmit=function(e){
    e.preventDefault();
    if(props.requireLogin&&!widgetIsLoggedIn()){ showH5Toast('请先登录'); navTo('login'); return; }
    var fields={};
    var missing=[];
    form.querySelectorAll('input,textarea').forEach(function(inp){
      if(!inp.name) return;
      var val=(inp.value||'').trim();
      fields[inp.name]=val;
      var labelEl=inp.closest('.mb-field');
      var labelTxt=labelEl&&labelEl.querySelector('label')?labelEl.querySelector('label').textContent:'';
      var fieldDef=(props.fields||[]).find(function(f){ return (f.key||'')===inp.name; });
      if(fieldDef&&fieldDef.required&&!val) missing.push(labelTxt||fieldDef.label||inp.name);
    });
    if(missing.length){ showH5Toast('请填写：'+missing.join('、')); return; }
    fields.nickname=fields.name||fields.nickname||'访客';
    fetch(apiBase+'/message/submit.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({instance_id:cid,fields:fields})})
      .then(function(r){return r.json();}).then(function(j){ showH5Toast((j&&j.data&&j.data.message)||(j&&j.msg)||'提交失败'); if(j&&j.code===0) form.reset(); })
      .catch(function(){ showH5Toast('网络错误'); });
  };
}
function renderMessageBoard(props,cid){
  var boxId=wgBoxId('msg',cid);
  wgMount(boxId,cid,props,function(el,p){ el.innerHTML=buildMessageBoardHtml(p,cid); bindMessageBoard(el,cid,p); });
  setTimeout(function(){ var el=document.getElementById(boxId); if(el) bindMessageBoard(el,cid,props); },50);
  return '<div class="component message-board" id="'+boxId+'"'+instAttr(cid)+'>'+buildMessageBoardHtml(props,cid)+'</div>';
}
function buildQuizHtml(props,questions,cid){
  var qs=(questions||props.questions||[]);
  if(!qs.length) return '<div class="quiz-box"><div class="mb-title">'+escHtml(props.title||'在线答题')+'</div><p style="color:#999">暂无题目</p></div>';
  var body=qs.map(function(q,i){
    var opts=(q.options||[]).map(function(o,j){
      var tp=q.type==='multi'?'checkbox':'radio';
      return '<label class="quiz-opt"><input type="'+tp+'" name="q'+i+'" value="'+formEsc(String(o))+'"> '+formEsc(String(o))+'</label>';
    }).join('');
    return '<div class="quiz-q"><div class="quiz-q-title">'+(i+1)+'. '+escHtml(q.question||'')+'</div>'+opts+'</div>';
  }).join('');
  return '<div class="quiz-box"><div class="mb-title">'+escHtml(props.title||'在线答题')+'</div><form class="quiz-form" data-instance="'+formEsc(cid||'')+'">'+body+'<button type="submit" class="mb-submit">提交答案</button></form></div>';
}
function bindQuiz(root,cid,props,questions){
  var form=root.querySelector('.quiz-form'); if(!form) return;
  form.onsubmit=function(e){
    e.preventDefault();
    if(props.requireLogin&&!widgetIsLoggedIn()){ showH5Toast('请先登录'); navTo('login'); return; }
    var qs=questions||props.questions||[];
    var answers={};
    qs.forEach(function(q,i){
      var nodes=form.querySelectorAll('[name="q'+i+'"]');
      if(q.type==='multi'){ answers[i]=[].filter.call(nodes,function(n){return n.checked;}).map(function(n){return n.value;}).join(','); }
      else { var c=form.querySelector('[name="q'+i+'"]:checked'); answers[i]=c?c.value:''; }
    });
    fetch(apiBase+'/quiz/submit.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({instance_id:cid,answers:answers})})
      .then(function(r){return r.json();}).then(function(j){
        if(j&&j.code===0&&j.data){ root.innerHTML='<div class="quiz-result">得分 '+j.data.score+'/'+j.data.total+(j.data.passed?' · 通过':' · 未通过')+'</div>'; }
        else showH5Toast((j&&j.msg)||'提交失败');
      }).catch(function(){ showH5Toast('网络错误'); });
  };
}
function renderQuiz(props,cid){
  var boxId=wgBoxId('quiz',cid);
  var paint=function(p,questions){ var el=document.getElementById(boxId); if(el){ el.innerHTML=buildQuizHtml(p,questions,cid); bindQuiz(el,cid,p,questions); } };
  if(guideCapture||!cid){ paint(props,props.questions||[]); }
  else {
    fetch(apiBase+'/quiz/get.php?id='+encodeURIComponent(cid)).then(function(r){return r.json();}).then(function(j){
      if(j&&j.code===0&&j.data) paint(Object.assign({},props,j.data.props||{}), j.data.questions||[]);
      else paint(props,props.questions||[]);
    }).catch(function(){ paint(props,props.questions||[]); });
    wgMount(boxId,cid,props,function(el,p){});
  }
  setTimeout(function(){ var el=document.getElementById(boxId); if(el) bindQuiz(el,cid,props,props.questions); },80);
  return '<div class="component" id="'+boxId+'"'+instAttr(cid)+'>'+buildQuizHtml(props,props.questions,cid)+'</div>';
}
function buildCheckinHtml(props,status){
  status=status||{};
  var checked=status.checkedToday;
  var btnText=checked?'今日已打卡':(props.buttonText||'立即打卡');
  return '<div class="checkin-activity"><div class="me-title">'+escHtml(props.title||'每日打卡')+'</div><div class="me-sub">'+escHtml(props.subtitle||'')+'</div><div class="ca-stats"><span>连续 '+ (status.streak||0) +' 天</span><span>累计 '+ (status.total||0) +' 次</span></div><button type="button" class="ca-btn'+(checked?' done':'')+'" data-done="'+(checked?'1':'0')+'">'+escHtml(btnText)+'</button></div>';
}
function bindCheckin(root,cid,props){
  var btn=root.querySelector('.ca-btn'); if(!btn||btn.getAttribute('data-done')==='1') return;
  btn.onclick=function(){
    if(props.requireLogin&&!widgetIsLoggedIn()){ showH5Toast('请先登录'); navTo('login'); return; }
    fetch(apiBase+'/checkin/do.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({instance_id:cid})})
      .then(function(r){return r.json();}).then(function(j){
        showH5Toast((j&&j.data&&j.data.message)||(j&&j.msg)||'打卡失败');
        if(j&&j.code===0) loadCheckinStatus(cid,props,root.id);
      }).catch(function(){ showH5Toast('网络错误'); });
  };
}
function loadCheckinStatus(cid,props,boxId){
  fetch(apiBase+'/checkin/status.php?instance_id='+encodeURIComponent(cid),{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){
    var el=document.getElementById(boxId); if(!el) return;
    var st=(j&&j.code===0&&j.data)?j.data:{};
    if(st.userId) { window.__userId=st.userId; window.__USER_LOGGED_IN__=true; }
    el.innerHTML=buildCheckinHtml(Object.assign({},props,st.props||{}),st);
    bindCheckin(el,cid,Object.assign({},props,st.props||{}));
  }).catch(function(){});
}
function renderCheckinActivity(props,cid){
  var boxId=wgBoxId('cka',cid);
  wgMount(boxId,cid,props,function(el,p){ loadCheckinStatus(cid,p,boxId); });
  setTimeout(function(){ loadCheckinStatus(cid,props,boxId); },50);
  return '<div class="component" id="'+boxId+'"'+instAttr(cid)+'>'+buildCheckinHtml(props,{})+'</div>';
}
var _tencentMapLoading=false,_tencentMapQueue=[];
function loadTencentMapScript(key,cb){
  if(window.qq&&window.qq.maps){ cb(); return; }
  _tencentMapQueue.push(cb);
  if(_tencentMapLoading) return;
  _tencentMapLoading=true;
  var s=document.createElement('script');
  s.src='./assets/vendor/tencent-map/gl.js';
  s.onerror=function(){
    _tencentMapLoading=false;
    _tencentMapQueue.forEach(function(fn){ fn(); });
    _tencentMapQueue=[];
  };
  s.onload=function(){ _tencentMapQueue.forEach(function(fn){ fn(); }); _tencentMapQueue=[]; };
  document.head.appendChild(s);
}
function paintMap(boxId,props){
  var el=document.getElementById(boxId); if(!el) return;
  var h=Math.round((props.height||400)/2);
  el.style.height=h+'px';
  var key=props.tencentMapKey||'';
  if(!key){ el.innerHTML='<div class="map-fallback">请在 install.php 配置腾讯地图 Key</div>'; return; }
  loadTencentMapScript(key,function(){
    if(!window.TMap){ el.innerHTML='<div class="map-fallback">地图加载失败</div>'; return; }
    el.innerHTML='<div class="map-canvas" id="'+boxId+'-canvas"></div>';
    var center=new TMap.LatLng(parseFloat(props.latitude)||31.23, parseFloat(props.longitude)||121.47);
    var map=new TMap.Map(boxId+'-canvas',{center:center,zoom:parseInt(props.zoom,10)||14});
    (props.markers||[]).forEach(function(m){
      if(m.latitude&&m.longitude) new TMap.MultiMarker({map:map,geometries:[{position:new TMap.LatLng(parseFloat(m.latitude),parseFloat(m.longitude))}]});
    });
  });
}
function renderMap(props,cid){
  var boxId=wgBoxId('map',cid);
  if(guideCapture){ return '<div class="component map-box" id="'+boxId+'" style="height:'+Math.round((props.height||400)/2)+'px"'+instAttr(cid)+'><div class="map-fallback">地图预览</div></div>'; }
  if(cid){
    fetch(apiBase+'/map/get.php?id='+encodeURIComponent(cid)).then(function(r){return r.json();}).then(function(j){
      if(j&&j.code===0&&j.data&&j.data.props) paintMap(boxId,Object.assign({},props,j.data.props));
      else paintMap(boxId,props);
    }).catch(function(){ paintMap(boxId,props); });
  } else setTimeout(function(){ paintMap(boxId,props); },30);
  return '<div class="component map-box" id="'+boxId+'" style="height:'+Math.round((props.height||400)/2)+'px"'+instAttr(cid)+'><div class="map-fallback">地图加载中...</div></div>';
}
function innerGridNav(props){
  var style=props.gridStyle||'grid';
  var cols=props.columns||4;
  var items=props.items||[];
  if(style==='magic') items=items.slice(0,3);
  var head='';
  if(props.title) head='<div class="grid-nav-head"><strong>'+escHtml(props.title)+'</strong>'+(props.subtitle?'<span>'+escHtml(props.subtitle)+'</span>':'')+'</div>';
  var cells=items.map(function(item,idx){
    var iconSrc=item.icon?imgThumbUrl(item.icon):'';
    var icon=iconSrc?'<img class="grid-icon" src="'+iconSrc+'" alt="">':'<div class="grid-icon grid-icon-ph"></div>';
    var inner='';
    if(style==='magic'&&idx===0){
      var coverImg=iconSrc?'<img class="magic-main-img" src="'+iconSrc+'" alt="">':'<div class="magic-main-ph"></div>';
      inner='<div class="magic-main-cover">'+coverImg+'</div><span class="magic-main-label">'+escHtml(item.text||'')+'</span>';
    }else{
      inner=icon+'<span class="grid-text">'+escHtml(item.text||'')+'</span>';
    }
    var magicCls='';
    if(style==='magic'){
      if(idx===0) magicCls=' magic-main';
      else if(idx===1) magicCls=' magic-r1';
      else if(idx===2) magicCls=' magic-r2';
    }
    var cardBg=style==='card'?' style="background:'+(item.bgColor||'#f5f7fa')+'"':'';
    var href=resolveLinkHref(item.link);
    if(href.indexOf('#')===0) return '<a class="grid-item'+magicCls+'" href="'+href+'"'+cardBg+' onclick="navByHref(\''+href+'\',event)">'+inner+'</a>';
    if(href) return '<a class="grid-item'+magicCls+'" href="'+href+'"'+cardBg+' target="_blank" rel="noopener">'+inner+'</a>';
    return '<div class="grid-item'+magicCls+'"'+cardBg+'>'+inner+'</div>';
  }).join('');
  var gridStyle='';
  if(style==='magic') gridStyle='grid-template-columns:2fr 1fr';
  else if(style==='grid') gridStyle='grid-template-columns:repeat('+cols+',1fr)';
  return head+cells;
}
function renderGridNav(props,cid){
  var boxId=wgBoxId('gn',cid);
  var style=props.gridStyle||'grid';
  var cols=props.columns||4;
  wgMount(boxId,cid,props,function(el,p){
    var st=p.gridStyle||'grid';
    var c=p.columns||4;
    el.className='component grid-nav style-'+st;
    if(st==='magic') el.style.cssText='grid-template-columns:2fr 1fr';
    else if(st==='grid') el.style.cssText='grid-template-columns:repeat('+c+',1fr)';
    else el.style.cssText='';
    el.innerHTML=innerGridNav(p);
  });
  var gridStyle='';
  if(style==='magic') gridStyle='grid-template-columns:2fr 1fr';
  else if(style==='grid') gridStyle='grid-template-columns:repeat('+cols+',1fr)';
  return '<div class="component grid-nav style-'+style+'" id="'+boxId+'" style="'+gridStyle+'"'+instAttr(cid)+'>'+innerGridNav(props)+'</div>';
}

function displayUserName(u, logged) {
  if (!logged) return '未登录用户';
  return (u.nickname || u.username || u.phone || '用户').trim();
}
function userProfileHtml(u, logged, props) {
  var avatar = u.avatar ? '<img src="' + u.avatar + '" alt="">' : '👤';
  var name = displayUserName(u, logged);
  var tags = (logged && u.member_level_name ? '<span class="uc-tag">' + u.member_level_name + '</span>' : '') + (logged ? '<span class="uc-tag">会员</span>' : '');
  var clickCls = logged ? '' : ' uc-profile-clickable';
  var clickEvt = logged ? '' : ' onclick="handleUserLogin()"';
  var actions = logged ? '<button type="button" class="uc-logout-btn" onclick="doLogout()">退出</button>' : '';
  return '<div class="uc-header"><div class="uc-profile' + clickCls + '"' + clickEvt + '><div class="uc-avatar">' + avatar + '</div><div><div class="uc-name">' + name + '</div><div class="uc-tags">' + tags + '</div></div></div><div class="uc-actions">' + actions + '</div></div>';
}
function userAssetsHtml(u, d) {
  return '<div class="uc-assets">' +
    '<div class="uc-asset uc-asset-click" onclick="navTo(\'points-logs\')"><div class="uc-asset-val">' + ((u&&u.points)||0) + '</div><div class="uc-asset-lbl">积分</div></div>' +
    '<div class="uc-asset uc-asset-click" onclick="navTo(\'wallet-logs\')"><div class="uc-asset-val">' + ((u&&u.balance)||0) + '</div><div class="uc-asset-lbl">余额</div></div>' +
    '<div class="uc-asset uc-asset-click" onclick="navTo(\'coupon-list\')"><div class="uc-asset-val">' + ((d&&d.coupon_count)||0) + '</div><div class="uc-asset-lbl">优惠券</div></div>' +
    '<div class="uc-asset"><div class="uc-asset-val">' + ((u&&u.deposit)||0) + '</div><div class="uc-asset-lbl">押金</div></div></div>';
}
function getVipOpenConfig(){
  var el=document.querySelector('.uc-vip[data-vip-config]');
  var cfg={levelId:6,payType:'balance',deductAmount:99,deductPoints:999};
  if(el){ try{ cfg=JSON.parse(decodeURIComponent(el.getAttribute('data-vip-config')||'')); }catch(e){} }
  return cfg;
}
function renderUserCenter(props, cid) {
  var boxId = 'uc-' + (cid || Math.random().toString(36).slice(2));
  if (guideCapture) {
    return '<div class="component user-center" id="' + boxId + '" data-guide-point="mine:user"' + instAttr(cid) + '><div class="article-empty" style="padding:24px">用户中心</div></div>';
  }
  setTimeout(function(){ loadUserCenter(boxId, props); }, 50);
  return '<div class="component user-center" id="' + boxId + '"' + instAttr(cid) + ' data-props="' + encodeURIComponent(JSON.stringify(props)) + '"><div class="article-empty">加载中...</div></div>';
}
function paintUserCenterDemo(el, props) {
  el.innerHTML = userProfileHtml({}, false, props) + userAssetsHtml({}, {});
}
function paintUserCenterData(el, d, props) {
  var u = (d && d.user) || {};
  var logged = !!(d && d.logged_in);
  window.__USER_LOGGED_IN__ = logged;
  if (logged && u.id) window.__userId = u.id;
  else window.__userId = 0;
  el.innerHTML = userProfileHtml(u, logged, props) + userAssetsHtml(u, d || {});
}
function loadUserCenter(boxId, props) {
  var el = document.getElementById(boxId); if (!el) return;
  if (guideCapture) { el.innerHTML = '<div class="article-empty" style="padding:24px">用户中心</div>'; return; }
  fetch(apiBase + '/user/center.php', { credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(json){
    el = document.getElementById(boxId); if (!el) return;
    if (!json || json.code !== 0) { paintUserCenterDemo(el, props); return; }
    paintUserCenterData(el, json.data || {}, props);
  }).catch(function(){ el = document.getElementById(boxId); if (el) paintUserCenterDemo(el, props); });
}
async function openVip() {
  var cfg=getVipOpenConfig();
  var tip=cfg.payType==='points' ? ('扣除'+cfg.deductPoints+'积分') : ('扣除¥'+cfg.deductAmount+'余额');
  if(!confirm('开通VIP将'+tip+'（积分达标可免扣费），确认开通？')) return;
  var res = await fetch(apiBase + '/user/vip_open.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({level_id:cfg.levelId||6,pay_type:cfg.payType||'balance',deduct_amount:cfg.deductAmount||99,deduct_points:cfg.deductPoints||999}) });
  var json = await res.json();
  showH5Toast(json.message || (json.code===0 ? '开通成功' : '开通失败'));
  if(json.code===0) renderApp(parseRoute().path);
}
function renderWalletRechargePage() {
  return renderSubPageNav('余额充值') + '<div class="virtual-page wallet-page"><h3>余额充值</h3><p style="color:#999;font-size:13px">演示环境：输入金额后直接入账</p><div class="form-row"><label>充值金额</label><input id="recharge_amount" type="number" min="1" step="0.01" placeholder="100"></div><button class="btn" onclick="submitWalletRecharge()">确认充值</button></div>';
}
async function submitWalletRecharge() {
  var amount=parseFloat((document.getElementById('recharge_amount')||{}).value||'0');
  if(!amount||amount<=0){ showH5Toast('请输入有效金额'); return; }
  var res=await fetch(apiBase+'/wallet/recharge.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({amount:amount})});
  var json=await res.json();
  showH5Toast(json.message||(json.code===0?'充值成功':'失败'));
  if(json.code===0) navTo('wallet-logs');
}
function renderWalletLogsPage() {
  setTimeout(loadWalletLogs,0);
  return renderSubPageNav('余额明细') + '<div class="virtual-page" id="wallet-logs-page"><div class="article-empty">加载中...</div><p style="text-align:center;margin-top:12px"><button class="btn" onclick="navTo(\'wallet-recharge\')">去充值</button></p></div>';
}
function loadWalletLogs() {
  var el=document.getElementById('wallet-logs-page'); if(!el) return;
  fetch(apiBase+'/wallet/logs.php',{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){
    if(j.code!==0){ el.innerHTML='<div class="article-empty">'+(j.message||'请先登录')+'</div>'; return; }
    var list=(j.data&&j.data.list)||[];
    if(!list.length){ el.innerHTML='<div class="article-empty">暂无记录</div>'; return; }
    el.innerHTML=list.map(function(it){
      var sign=parseFloat(it.amount)>=0?'+':'';
      return '<div class="log-row"><div><strong>'+sign+it.amount+'</strong><p>'+(it.remark||it.type||'')+'</p></div><small>'+(it.created_at||'')+'</small></div>';
    }).join('');
  }).catch(function(){ el.innerHTML='<div class="article-empty">加载失败</div>'; });
}
function renderPointsLogsPage() {
  setTimeout(loadPointsLogs,0);
  return renderSubPageNav('积分明细') + '<div class="virtual-page" id="points-logs-page"><div class="article-empty">加载中...</div></div>';
}
function loadPointsLogs() {
  var el=document.getElementById('points-logs-page'); if(!el) return;
  fetch(apiBase+'/points/logs.php',{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){
    if(j.code!==0){ el.innerHTML='<div class="article-empty">'+(j.message||'请先登录')+'</div>'; return; }
    var list=(j.data&&j.data.list)||[];
    if(!list.length){ el.innerHTML='<div class="article-empty">暂无记录</div>'; return; }
    el.innerHTML=list.map(function(it){
      var sign=(parseInt(it.points,10)||0)>=0?'+':'';
      return '<div class="log-row"><div><strong>'+sign+it.points+'</strong><p>'+(it.remark||it.type||'')+'</p></div><small>'+(it.created_at||'')+'</small></div>';
    }).join('');
  }).catch(function(){ el.innerHTML='<div class="article-empty">加载失败</div>'; });
}

function isWechatBrowser() {
  return /MicroMessenger/i.test(navigator.userAgent || '');
}
function handleUserLogin() {
  if (window.__USER_LOGGED_IN__) return;
  if (isWechatBrowser()) { doWechatH5Login(); return; }
  navTo('login');
}
async function doWechatH5Login() {
  try {
    var back = location.href.split('#')[0] + '#mine';
    var res = await fetch(apiBase + '/auth/wx_oauth.php?action=url&redirect=' + encodeURIComponent(back));
    var json = await res.json();
    if (json.code === 0 && json.data && json.data.url) { location.href = json.data.url; return; }
    showH5Toast(json.message || '微信登录暂不可用，请检查公众号配置');
  } catch (e) { showH5Toast('微信登录失败'); }
}
function renderLoginPage() {
  return renderSubPageNav('登录') + '<div class="auth-page"><h2>账号登录</h2><div class="auth-form">' +
    '<div class="form-row"><label>手机号</label><input id="login_phone" type="tel" placeholder="请输入手机号"></div>' +
    '<div class="form-row"><label>密码</label><input id="login_password" type="password" placeholder="请输入密码"></div>' +
    '<div class="auth-actions"><button type="button" class="btn-primary" onclick="submitLogin()">提交</button>' +
    '<button type="button" class="btn-link" onclick="navTo(\'register\')">新用户注册</button>' +
    '<button type="button" class="btn-link" onclick="navTo(\'forgot-password\')">忘记密码</button></div></div></div>';
}
function renderRegisterPage() {
  return renderSubPageNav('注册') + '<div class="auth-page"><h2>新用户注册</h2><div class="auth-form">' +
    '<div class="form-row"><label>手机号</label><input id="reg_phone" type="tel" placeholder="请输入手机号"></div>' +
    '<div class="form-row"><label>密码</label><input id="reg_password" type="password" placeholder="设置密码"></div>' +
    '<div class="form-row"><label>验证码</label><div class="auth-captcha-row"><input id="reg_captcha" placeholder="验证码"><img src="' + apiBase + '/captcha.php" alt="验证码" onclick="this.src=apiBase+\'/captcha.php?\'+Date.now()"></div></div>' +
    '<div class="auth-actions"><button type="button" class="btn-primary" onclick="submitRegister()">提交</button>' +
    '<button type="button" class="btn-link" onclick="navTo(\'login\')">已有账号登录</button></div></div></div>';
}
function renderForgotPasswordPage() {
  return renderSubPageNav('忘记密码') + '<div class="auth-page"><h2>忘记密码</h2><div class="auth-form">' +
    '<div class="form-row"><label>手机号</label><input id="fp_phone" type="tel" placeholder="注册手机号"></div>' +
    '<p style="font-size:13px;color:#999;line-height:1.5">提交后请联系管理员或使用注册手机号找回。线上自助重置功能即将上线。</p>' +
    '<div class="auth-actions"><button type="button" class="btn-primary" onclick="submitForgotPassword()">提交</button>' +
    '<button type="button" class="btn-link" onclick="navTo(\'login\')">返回登录</button></div></div></div>';
}
async function submitLogin() {
  var phone = (document.getElementById('login_phone')||{}).value || '';
  var password = (document.getElementById('login_password')||{}).value || '';
  if (!phone || !password) { showH5Toast('请输入手机号和密码'); return; }
  var res = await fetch(apiBase + '/auth/login.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({phone:phone, password:password}) });
  var json = await res.json();
  if (json.code === 0) { showH5Toast('登录成功'); navTo('mine'); }
  else showH5Toast(json.message || '登录失败');
}
async function submitRegister() {
  var phone = (document.getElementById('reg_phone')||{}).value || '';
  var password = (document.getElementById('reg_password')||{}).value || '';
  var captcha = (document.getElementById('reg_captcha')||{}).value || '';
  if (!phone || !password || !captcha) { showH5Toast('请填写完整信息'); return; }
  var invite = (parseRoute().query || {}).invite || '';
  var res = await fetch(apiBase + '/auth/register.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({phone:phone, password:password, captcha:captcha, nickname:phone, invite_code:invite}) });
  var json = await res.json();
  if (json.code === 0) { showH5Toast(json.message || '注册成功'); navTo('mine'); }
  else showH5Toast(json.message || '注册失败');
}
function submitForgotPassword() {
  var phone = (document.getElementById('fp_phone')||{}).value || '';
  if (!phone) { showH5Toast('请输入手机号'); return; }
  showH5Toast('已记录找回申请：' + phone + '。请联系管理员重置密码，或尝试重新注册。');
  navTo('login');
}
function showLogin() { navTo('login'); }

function renderUserVip(props, cid) {
  var boxId = 'uvip-' + (cid || Math.random().toString(36).slice(2));
  var cfg = {levelId: props.levelId||6, payType: props.payType||'balance', deductAmount: props.deductAmount||99, deductPoints: props.deductPoints||999};
  var cfgAttr = ' data-vip-config="' + encodeURIComponent(JSON.stringify(cfg)) + '"';
  if (guideCapture) {
    return '<div class="component uc-vip" id="' + boxId + '"' + cfgAttr + instAttr(cid) + '><div><div class="uc-vip-title">' + (props.title||'VIP至尊会员卡') + '</div><div class="uc-vip-sub">' + (props.subtitle||'开通立享价格特权') + '</div></div><button class="uc-vip-btn" type="button">立即开通</button></div>';
  }
  if (cid) {
    wgMount(boxId, cid, props, function(el, p) {
      var t = el.querySelector('.uc-vip-title'); if (t) t.textContent = p.title || 'VIP至尊会员卡';
      var s = el.querySelector('.uc-vip-sub'); if (s) s.textContent = p.subtitle || '开通立享价格特权、配送服务、专属服务';
    });
  }
  var title = props.title || 'VIP至尊会员卡';
  var sub = props.subtitle || '开通立享价格特权、配送服务、专属服务';
  return '<div class="component uc-vip" id="' + boxId + '"' + cfgAttr + instAttr(cid) + '><div><div class="uc-vip-title">' + title + '</div><div class="uc-vip-sub">' + sub + '</div></div><button class="uc-vip-btn" type="button" onclick="openVip()">立即开通</button></div>';
}
function renderUserBenefits(props, cid) {
  var boxId = 'uben-' + (cid || Math.random().toString(36).slice(2));
  if (guideCapture) {
    return '<div class="component uc-benefits" id="' + boxId + '"' + instAttr(cid) + '><div class="uc-benefits-title">会员权益</div><div class="uc-benefit-icons"><div class="uc-benefit-item"><div class="uc-benefit-ico">👑</div>专属折扣</div></div></div>';
  }
  if (cid) {
    wgMount(boxId, cid, props, function(el, p) { paintUserBenefits(el, p); });
  }
  setTimeout(function(){ loadUserBenefits(boxId, props); }, 50);
  return '<div class="component uc-benefits" id="' + boxId + '"' + instAttr(cid) + '><div class="article-empty">加载中...</div></div>';
}
function paintUserBenefits(el, props, list) {
  list = list || props.items || ['专属折扣','新品上市','满减券','果蔬礼盒','生日礼包'];
  var title = props.title || '会员权益 · 9项权益';
  el.innerHTML = '<div class="uc-benefits-title">' + title + '</div><div class="uc-benefit-icons">' +
    list.map(function(b){ return '<div class="uc-benefit-item"><div class="uc-benefit-ico">👑</div>' + b + '</div>'; }).join('') + '</div>';
}
function loadUserBenefits(boxId, props) {
  var el = document.getElementById(boxId); if (!el) return;
  fetch(apiBase + '/user/center.php', { credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(json){
    el = document.getElementById(boxId); if (!el) return;
    if (!json || json.code !== 0) { paintUserBenefits(el, props); return; }
    paintUserBenefits(el, props, (json.data && json.data.benefits) || props.items);
  }).catch(function(){ el = document.getElementById(boxId); if (el) paintUserBenefits(el, props); });
}
function renderUserOrders(props, cid) {
  var boxId = 'uord-' + (cid || Math.random().toString(36).slice(2));
  if (guideCapture) {
    return '<div class="component uc-orders" id="' + boxId + '" data-guide-point="mine:orders"' + instAttr(cid) + '><div class="uc-orders-head"><strong>我的订单 · 订单速查</strong></div><div class="uc-order-icons"><div class="uc-order-item"><div class="uc-order-ico">💳</div>待付款</div><div class="uc-order-item"><div class="uc-order-ico">📦</div>待发货</div></div></div>';
  }
  if (!window.HAS_COMMERCE) return '<div class="component uc-orders" id="' + boxId + '"' + instAttr(cid) + '><div class="article-empty">需同时启用商品组件</div></div>';
  if (cid) {
    wgMount(boxId, cid, props, function(el, p) { paintUserOrders(el, p, {}); });
  }
  setTimeout(function(){ loadUserOrders(boxId, props); }, 50);
  return '<div class="component uc-orders" id="' + boxId + '"' + instAttr(cid) + '><div class="article-empty">加载中...</div></div>';
}
function paintUserOrders(el, props, oc) {
  oc = oc || {};
  var orderBadge = function(n){ return n > 0 ? '<span class="uc-order-badge">' + n + '</span>' : ''; };
  var title = props.title || '我的订单 · 订单速查';
  el.innerHTML = '<div class="uc-orders-head"><strong>' + title + '</strong><a href="#" onclick="navTo(\'' + orderRouteBase() + '\');return false">全部订单 ›</a></div><div class="uc-order-icons">' +
    '<div class="uc-order-item" onclick="navTo(\'' + orderNavTarget('pending_pay') + '\')"><div class="uc-order-ico">💳</div>待付款' + orderBadge(oc.pending_pay||0) + '</div>' +
    '<div class="uc-order-item" onclick="navTo(\'' + orderNavTarget('pending_ship') + '\')"><div class="uc-order-ico">📦</div>待发货' + orderBadge(oc.pending_ship||0) + '</div>' +
    '<div class="uc-order-item" onclick="navTo(\'' + orderNavTarget('shipping') + '\')"><div class="uc-order-ico">🚚</div>已发货' + orderBadge(oc.shipping||0) + '</div>' +
    '<div class="uc-order-item" onclick="navTo(\'' + orderNavTarget('completed') + '\')"><div class="uc-order-ico">✅</div>已完成' + orderBadge(oc.completed||0) + '</div></div>';
}
function loadUserOrders(boxId, props) {
  var el = document.getElementById(boxId); if (!el) return;
  fetch(apiBase + '/user/center.php', { credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(json){
    el = document.getElementById(boxId); if (!el) return;
    if (!json || json.code !== 0) { paintUserOrders(el, props, {}); return; }
    paintUserOrders(el, props, (json.data && json.data.order_counts) || {});
  }).catch(function(){ el = document.getElementById(boxId); if (el) paintUserOrders(el, props, {}); });
}
function renderUserCommunity(props, cid) {
  var boxId = 'ucm-' + (cid || '');
  var inner = function(p) {
    var title = p.title || '会员社区 · 大家都在买';
    var sub = p.subtitle || '发现好物、分享心得';
    var linkText = p.linkText || '查看全部 ›';
    var href = resolveLinkHref(p.link);
    if (!href) href = '#product-list';
    var more = href.indexOf('#') === 0
      ? '<a href="' + href + '" onclick="navByHref(\'' + href + '\', event)">' + linkText + '</a>'
      : '<a href="' + href + '" target="_blank" rel="noopener">' + linkText + '</a>';
    return '<div class="uc-orders-head"><strong>' + title + '</strong>' + more + '</div><p style="font-size:12px;color:#999;margin:8px 0 0">' + sub + '</p>';
  };
  if (cid && !guideCapture) {
    wgMount('ucm-' + cid, cid, props, function(el, p) { el.innerHTML = inner(p); });
  }
  return '<div class="component uc-community" id="ucm-' + (cid||'') + '"' + instAttr(cid) + '>' + inner(props) + '</div>';
}

window.HAS_COMMERCE = true;
function showH5Modal(content, title) {
  return new Promise(function(resolve) {
    var mask = document.getElementById('h5-app-modal');
    if (!mask) {
      mask = document.createElement('div');
      mask.id = 'h5-app-modal';
      mask.className = 'h5-app-modal-mask';
      mask.innerHTML = '<div class="h5-app-modal-box"><div class="h5-app-modal-title"></div><div class="h5-app-modal-content"></div><button type="button" class="btn h5-app-modal-btn">确定</button></div>';
      document.body.appendChild(mask);
      mask.querySelector('.h5-app-modal-btn').addEventListener('click', function() {
        mask.style.display = 'none';
        if (mask._resolve) { var fn = mask._resolve; mask._resolve = null; fn(); }
      });
    }
    mask.querySelector('.h5-app-modal-title').textContent = title || '提示';
    mask.querySelector('.h5-app-modal-content').textContent = content || '';
    mask._resolve = resolve;
    mask.style.display = 'flex';
  });
}
function showLoginOverlay(msg) {
  var el = document.getElementById('h5-login-overlay');
  if (!el) {
    el = document.createElement('div');
    el.id = 'h5-login-overlay';
    el.className = 'h5-login-overlay';
    el.innerHTML = '<div class="h5-login-box"><p id="h5-login-msg">请先登录</p><button type="button" class="btn" onclick="navTo(\'login\');hideLoginOverlay();">去登录</button><button type="button" class="btn btn-ghost" onclick="hideLoginOverlay()">取消</button></div>';
    document.body.appendChild(el);
  }
  var m = document.getElementById('h5-login-msg');
  if (m) m.textContent = msg || '请先登录后再操作';
  el.style.display = 'flex';
}
function hideLoginOverlay() {
  var el = document.getElementById('h5-login-overlay');
  if (el) el.style.display = 'none';
}
function apiNeedLogin(j) {
  return !j || j.code !== 0 || !j.data || j.data.logged_in === false;
}
function addToCart(productId, qty) {
  if (!window.HAS_COMMERCE) { showLoginOverlay('请先添加用户系统和商品组件'); return; }
  if (!window.__USER_LOGGED_IN__) { showLoginOverlay('请先登录'); return; }
  fetch(apiBase + '/cart/add.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({product_id:productId, quantity:qty||1}) })
    .then(function(r){ return r.json(); }).then(function(j){
      if (j.code !== 0) {
        if (j.code === 401) { showLoginOverlay(j.message || '请先登录'); return; }
        showH5Toast(j.message || '加入购物车失败'); return;
      }
      showH5Modal(j.message || '加入购物车成功').then(function(){
        if (typeof loadCartPage === 'function') loadCartPage();
      });
    }).catch(function(){ showH5Toast('请求失败'); });
}
function buyNow(pid) {
  fetch(apiBase + '/order/create.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({from_cart:0, product_id:pid, quantity:1, address_name:'', address_phone:'', address_detail:''}) })
    .then(function(r){ return r.json(); }).then(function(j){
      if(j.code!==0){ showH5Toast(j.message||'失败'); return; }
      navTo('checkout?order_id=' + j.data.order_id);
    });
}
function renderCartTitleBar(comp) {
  var p = comp.props || {};
  var title = p.title || '购物车';
  var sub = p.subtitle || '共0件商品';
  var more = p.showMoreLink && p.moreText ? '<a class="tb-more" href="#">' + (p.moreText || '查看更多 ›') + '</a>' : '';
  return '<div class="component title-bar" data-cart-title-bar="1"><h2>' + title + more + '</h2><p class="cart-subtitle-count">' + sub + '</p></div>';
}
function updateCartTitleBarCount(list) {
  var qty = (list || []).reduce(function(sum, it){ return sum + (parseInt(it.quantity, 10) || 0); }, 0);
  var text = '共' + qty + '件商品';
  document.querySelectorAll('[data-cart-title-bar] .cart-subtitle-count, [data-cart-title-bar] p.sub, [data-cart-title-bar] p').forEach(function(el){
    if (el.closest && el.closest('[data-cart-title-bar]')) el.textContent = text;
  });
}
function cartPageCanvasLayout() {
  var pg = findPage('cart');
  if (!pg) return { before: '', titleBar: '', after: '' };
  var comps = pageComponents(pg).filter(function(c){ return c.visible !== false; });
  if (!comps.length) return { before: '', titleBar: '', after: '' };
  var anchorIdx = -1;
  for (var i = 0; i < comps.length; i++) {
    if (comps[i].type === 'titleBar') { anchorIdx = i; break; }
  }
  if (anchorIdx < 0) {
    return { before: renderPageComponents(pg), titleBar: '', after: '' };
  }
  var before = comps.slice(0, anchorIdx);
  var after = comps.slice(anchorIdx + 1);
  var out = { before: '', titleBar: renderCartTitleBar(comps[anchorIdx]), after: '' };
  if (before.length) out.before = renderPageComponents({ page_key: pg.page_key, components: before });
  if (after.length) out.after = renderPageComponents({ page_key: pg.page_key, components: after });
  return out;
}
function cartFootHtml() {
  return '<div class="cart-foot" id="cart-foot" style="display:none"><span>合计 <span class="cart-total" id="cart-total">¥0</span></span><button class="btn" onclick="goCheckout()">去结算</button></div>';
}
function cartCommerceBlockHtml() {
  return '<div class="cart-page cart-api-block" id="cart-page"><div class="article-empty">加载中...</div>' + cartFootHtml() + '</div>';
}
function renderCartPage() {
  setTimeout(loadCartPage, 0);
  var layout = cartPageCanvasLayout();
  var body = layout.before + '<div class="cart-head-block">' + layout.titleBar + cartCommerceBlockHtml() + '</div>' + layout.after;
  if (typeof isTabBarPage === 'function' && isTabBarPage('cart')) return body;
  var pg = findPage('cart');
  return renderSubPageNav((pg && pg.page_name) || '购物车') + body;
}
function loadCartPage() {
  var el = document.getElementById('cart-page'); if(!el) return;
  fetch(apiBase + '/cart/list.php', { credentials:'same-origin' }).then(function(r){ return r.json(); }).then(function(j){
    if (apiNeedLogin(j)) { el.innerHTML = '<div class="article-empty">'+(j&&j.message||'请先登录')+'</div><p style="text-align:center"><button class="btn" onclick="navTo(\'login\')">去登录</button></p>'; updateCartTitleBarCount([]); return; }
    var list = (j.data&&j.data.list)||[];
    updateCartTitleBarCount(list);
    if(!list.length){ el.innerHTML = '<div class="article-empty">购物车是空的</div>' + cartFootHtml(); return; }
    el.innerHTML = list.map(function(it){
      return '<div class="cart-item"><input type="checkbox" '+(it.selected?'checked':'')+' onchange="toggleCartItem('+it.id+',this.checked)"><img src="'+productImg(it)+'"><div class="cart-item-info"><div>'+it.name+'</div><div class="product-price">¥'+it.price+'</div><div class="cart-qty"><button onclick="changeCartQty('+it.id+','+(it.quantity-1)+')">-</button><span>'+it.quantity+'</span><button onclick="changeCartQty('+it.id+','+(it.quantity+1)+')">+</button><a href="#" onclick="removeCartItem('+it.id+');return false" style="margin-left:auto;color:#e74c3c;font-size:12px">删除</a></div></div></div>';
    }).join('') + cartFootHtml();
    var foot = document.getElementById('cart-foot'); if(foot){ foot.style.display='flex'; }
    var tot = document.getElementById('cart-total'); if(tot) tot.textContent = '¥' + ((j.data&&j.data.selected_total)||0);
  });
}
function changeCartQty(id, qty) { if(qty<1) return; fetch(apiBase+'/cart/update.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({id:id,quantity:qty})}).then(function(){ loadCartPage(); }); }
function toggleCartItem(id, sel) { fetch(apiBase+'/cart/update.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({id:id,selected:sel?1:0})}).then(function(){ loadCartPage(); }); }
function removeCartItem(id) { fetch(apiBase+'/cart/remove.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({id:id})}).then(function(){ loadCartPage(); }); }
function goCheckout() { navTo('checkout?from_cart=1'); }
function renderOrderDetailPage(query) {
  setTimeout(function(){ loadOrderDetailPage(query.id || ''); }, 0);
  return renderSubPageNav('订单详情') + '<div class="order-detail-page" id="order-detail-page"><div class="article-empty">加载中...</div></div>';
}
function loadOrderDetailPage(id) {
  var el = document.getElementById('order-detail-page'); if(!el || !id) return;
  fetch(apiBase + '/order/detail.php?id=' + encodeURIComponent(id), { credentials:'same-origin' }).then(function(r){ return r.json(); }).then(function(j){
    if (j.code !== 0 || !j.data) { el.innerHTML = '<div class="article-empty">'+(j.message||'加载失败')+'</div>'; return; }
    var o = j.data;
    var items = (o.items||[]).map(function(it){
      return '<div class="order-item-row"><img src="'+productImg(it)+'"><div><div>'+it.product_name+'</div><div style="color:#999;font-size:12px">¥'+it.price+' x '+it.quantity+'</div></div></div>';
    }).join('');
    var couponLine = (parseFloat(o.discount_amount||0) > 0)
      ? '<div class="detail-row"><span>优惠券</span><span>'+(o.coupon_name||'已使用')+' -¥'+o.discount_amount+'</span></div>' : '';
    el.innerHTML = '<div class="order-detail-card"><div class="order-card-head"><span>'+o.order_no+'</span><span>'+o.status_label+'</span></div>'+
      '<div class="detail-row"><span>下单时间</span><span>'+(o.created_at||'')+'</span></div>'+
      '<div class="detail-row"><span>商品总额</span><span>¥'+(o.total_amount||0)+'</span></div>'+
      couponLine +
      '<div class="detail-row"><span>实付金额</span><span style="color:#e74c3c;font-weight:700">¥'+(o.pay_amount||0)+'</span></div>'+
      '<div class="detail-row"><span>收货人</span><span>'+(o.address_name||'—')+'</span></div>'+
      '<div class="detail-row"><span>手机号码</span><span>'+(o.address_phone||'—')+'</span></div>'+
      '<div class="detail-row"><span>收货地址</span><span>'+(o.address_detail||'—')+'</span></div>'+
      '<div style="margin-top:12px">'+items+'</div></div>';
  }).catch(function(){ el.innerHTML = '<div class="article-empty">加载失败</div>'; });
}
function renderCheckoutPage(query) {
  setTimeout(function(){ loadCheckoutPage(query); }, 0);
  return renderSubPageNav('确认订单') + '<div class="checkout-page" id="checkout-page"><div class="article-empty">加载中...</div></div>';
}
function loadCheckoutPage(query) {
  var el = document.getElementById('checkout-page'); if(!el) return;
  var fromCart = (query.from_cart||'1')==='1';
  var orderId = query.order_id || '';
  el.innerHTML = '<div class="checkout-form"><div id="ck-addr-pick"></div><div class="form-row"><label>收货人</label><input id="ck_name" placeholder="姓名"></div><div class="form-row"><label>手机号</label><input id="ck_phone" placeholder="手机"></div><div class="form-row"><label>地址</label><textarea id="ck_addr" rows="2" placeholder="详细地址"></textarea></div><div class="pay-btns"><button class="btn-balance" onclick="payOrder('+(orderId||'0')+','+fromCart+',\'balance\')">余额支付</button></div></div>';
  window._checkoutFromCart = fromCart;
  window._checkoutOrderId = orderId;
  fetch(apiBase + '/address/list.php', { credentials:'same-origin' }).then(function(r){ return r.json(); }).then(function(j){
    if(!j || j.code!==0 || !j.data) return;
    var list = j.data.list || [];
    window.__ADDR_LIST__ = list;
    var def = list.filter(function(a){ return a.is_default; })[0] || list[0];
    if(def){
      document.getElementById('ck_name').value = def.name || '';
      document.getElementById('ck_phone').value = def.phone || '';
      document.getElementById('ck_addr').value = def.detail || '';
    }
    var pick = document.getElementById('ck-addr-pick');
    if(!pick || !list.length) return;
    pick.innerHTML = '<div class="form-row"><label>地址簿</label><select id="ck_addr_sel" onchange="fillCheckoutAddress(this.value)" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px"><option value="">手动填写</option>' +
      list.map(function(a){ return '<option value="'+a.id+'">'+(a.is_default?'[默认] ':'')+a.name+' '+a.phone+'</option>'; }).join('') + '</select></div>';
    if(def) document.getElementById('ck_addr_sel').value = def.id;
  });
}
function fillCheckoutAddress(id) {
  if(!id || !window.__ADDR_LIST__) return;
  var a = (window.__ADDR_LIST__||[]).filter(function(x){ return String(x.id)===String(id); })[0];
  if(!a) return;
  document.getElementById('ck_name').value = a.name || '';
  document.getElementById('ck_phone').value = a.phone || '';
  document.getElementById('ck_addr').value = a.detail || '';
}
function payOrder(existingId, fromCart, payType) {
  var body = { address_name: document.getElementById('ck_name').value, address_phone: document.getElementById('ck_phone').value, address_detail: document.getElementById('ck_addr').value };
  var chain = existingId && existingId !== '0' ? Promise.resolve({code:0,data:{order_id:existingId}}) : fetch(apiBase+'/order/create.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(Object.assign({from_cart:fromCart?1:0},body))}).then(function(r){return r.json();});
  chain.then(function(j){
    if(j.code!==0){ showH5Toast(j.message||'下单失败'); return; }
    var oid = j.data.order_id;
    return fetch(apiBase+'/order/pay.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({order_id:oid,pay_type:payType})}).then(function(r){return r.json();}).then(function(pj){
      if(pj.code!==0){ showH5Toast(pj.message||'支付失败'); return; }
      showH5Toast(pj.message||'支付成功'); navTo(orderRouteBase());
    });
  });
}
function orderRouteBase() {
  var tabBar = globalConfig.tabBar;
  if (tabBar && tabBar.enabled && tabBar.items) {
    for (var i = 0; i < tabBar.items.length; i++) {
      var pk = tabBar.items[i].page_key;
      if (pk === 'order' || pk === 'order-list') return pk;
    }
  }
  return 'order-list';
}
function orderNavTarget(status) {
  var base = orderRouteBase();
  return status ? (base + '?status=' + encodeURIComponent(status)) : base;
}
function orderListTabsHtml(active) {
  var tabs = [{k:'',l:'全部'},{k:'pending_pay',l:'待付款'},{k:'pending_ship',l:'待发货'},{k:'shipping',l:'已发货'},{k:'completed',l:'已完成'}];
  var base = orderRouteBase();
  return '<div class="order-tabs">' + tabs.map(function(t){
    var cls = 'order-tab' + ((active||'')===t.k ? ' active' : '');
    var hash = t.k ? orderNavTarget(t.k) : base;
    return '<a class="'+cls+'" href="#'+hash+'" onclick="navTo(\''+hash+'\');return false">'+t.l+'</a>';
  }).join('') + '</div>';
}
function orderPageOmitComp(c) {
  return c.type === 'gridNav';
}
function orderPageFilterComps(comps) {
  return (comps || []).filter(function(c){ return c.visible !== false && !orderPageOmitComp(c); });
}
function orderPageCanvasLayout(activeStatus) {
  var pg = findPageStrict('order') || findPageStrict('order-list');
  if (!pg) return { before: orderListTabsHtml(activeStatus), after: '' };
  var comps = pageComponents(pg).filter(function(c){ return c.visible !== false; });
  if (!comps.length) return { before: orderListTabsHtml(activeStatus), after: '' };
  var anchorIdx = -1;
  for (var i = 0; i < comps.length; i++) {
    if (comps[i].type === 'orderStatusAnchor' || comps[i].type === 'userOrders' || comps[i].type === 'titleBar') { anchorIdx = i; break; }
  }
  if (anchorIdx < 0) {
    var only = orderPageFilterComps(comps);
    return { before: (only.length ? renderPageComponents({ page_key: pg.page_key, components: only }) : '') + orderListTabsHtml(activeStatus), after: '' };
  }
  var before = orderPageFilterComps(comps.slice(0, anchorIdx));
  var after = orderPageFilterComps(comps.slice(anchorIdx + 1));
  var out = { before: '', after: '' };
  if (before.length) out.before = renderPageComponents({ page_key: pg.page_key, components: before });
  out.before += orderListTabsHtml(activeStatus);
  if (after.length) out.after = renderPageComponents({ page_key: pg.page_key, components: after });
  return out;
}
function orderCommerceBlockHtml() {
  return '<div class="order-api-block" id="order-list-body"><div class="article-empty">加载中...</div></div>';
}
function renderOrderPage(query) {
  var st = (query && query.status) || '';
  setTimeout(function(){ loadOrderListPage(st); }, 0);
  var layout = orderPageCanvasLayout(st);
  var body = layout.before + orderCommerceBlockHtml() + layout.after;
  if (typeof isTabBarPage === 'function' && isTabBarPage('order')) return body;
  var pg = findPageStrict('order');
  return renderSubPageNav((pg && pg.page_name) || '我的订单') + '<div class="order-list-page">' + body + '</div>';
}
function orderListPageLayoutHtml(activeStatus) {
  var layout = orderPageCanvasLayout(activeStatus);
  return layout.before + orderCommerceBlockHtml() + layout.after;
}
function orderListPageDecorHtml() {
  return orderListPageLayoutHtml('');
}
function renderOrderListPage(query) {
  return renderOrderPage(query || {});
}
function loadOrderListPage(status) {
  var el = document.getElementById('order-list-body'); if(!el) return;
  var url = apiBase + '/order/list.php?page=1';
  if(status) url += '&status=' + encodeURIComponent(status);
  fetch(url, { credentials:'same-origin' }).then(function(r){ return r.json(); }).then(function(j){
    if (apiNeedLogin(j)) { el.innerHTML = '<div class="article-empty">'+(j&&j.message||'请先登录')+'</div><p style="text-align:center;margin-top:12px"><button class="btn" onclick="navTo(\'login\')">去登录</button></p>'; return; }
    var list = (j.data&&j.data.list)||[];
    if(!list.length){ el.innerHTML = '<div class="article-empty">暂无订单</div>'; return; }
    el.innerHTML = list.map(function(o){
      var items = (o.items||[]).map(function(it){ return '<div class="order-item-row"><img src="'+productImg(it)+'"><span>'+it.product_name+' x'+it.quantity+'</span></div>'; }).join('');
      var acts = (o.status==='pending_pay') ? '<div class="order-acts" onclick="event.stopPropagation()"><button class="btn-pay" onclick="goPayOrder('+o.id+');return false">去付款</button><button class="btn-cancel" onclick="cancelPendingOrder('+o.id+');return false">取消订单</button></div>' : '';
      return '<div class="order-card" onclick="navTo(\'order-detail?id='+o.id+'\')" style="cursor:pointer"><div class="order-card-head"><span>'+o.order_no+'</span><span>'+o.status_label+'</span></div>'+items+'<div style="text-align:right;margin-top:8px;color:#e74c3c">¥'+o.pay_amount+'</div>'+acts+'</div>';
    }).join('');
  });
}
function goPayOrder(orderId) {
  navTo('checkout?order_id=' + orderId + '&from_cart=0');
}
function cancelPendingOrder(orderId) {
  if (!confirm('确定取消该订单？')) return;
  fetch(apiBase + '/order/cancel.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({ order_id: orderId }) })
    .then(function(r){ return r.json(); }).then(function(j){
      showH5Toast(j.message || (j.code === 0 ? '已取消' : '取消失败'));
      if (j.code === 0 && typeof loadOrderListPage === 'function') {
        var m = (location.hash || '').match(/status=([^&]+)/);
        loadOrderListPage(m ? decodeURIComponent(m[1]) : '');
      }
    });
}
function renderAddressListPage() {
  setTimeout(loadAddressListPage, 0);
  return renderSubPageNav('收货地址') + '<div class="sys-page" id="address-list-page"><div class="article-empty">加载中...</div></div>';
}
function loadAddressListPage() {
  var el = document.getElementById('address-list-page'); if(!el) return;
  fetch(apiBase + '/address/list.php', { credentials:'same-origin' }).then(function(r){ return r.json(); }).then(function(j){
    if (apiNeedLogin(j)) { el.innerHTML = '<div class="article-empty">'+(j&&j.message||'请先登录')+'</div><p style="text-align:center;margin-top:12px"><button class="btn" onclick="navTo(\'login\')">去登录</button></p>'; return; }
    var list = (j.data&&j.data.list)||[];
    window.__ADDR_LIST__ = list;
    var html = list.map(function(a){
      return '<div class="addr-card'+(a.is_default?' default':'')+'"><div class="addr-card-head"><span>'+a.name+'<span class="addr-phone">'+a.phone+'</span></span>'+(a.is_default?'<span style="color:#2ecc71;font-size:12px">默认</span>':'')+'</div><div class="addr-card-detail">'+a.detail+'</div><div class="addr-card-actions">' +
        (a.is_default?'':'<button type="button" onclick="setDefaultAddress('+a.id+')">设为默认</button>') +
        '<button type="button" onclick="editAddressForm('+a.id+')">编辑</button><button type="button" onclick="deleteAddress('+a.id+')">删除</button></div></div>';
    }).join('');
    html += '<div class="addr-form" id="addr-form-box"><strong id="addr-form-title">新增地址</strong><input type="hidden" id="addr_id" value="">' +
      '<div class="form-row"><label>收货人</label><input id="addr_name" placeholder="姓名"></div>' +
      '<div class="form-row"><label>手机号</label><input id="addr_phone" type="tel" placeholder="11位手机号"></div>' +
      '<div class="form-row"><label>详细地址</label><textarea id="addr_detail" rows="3" placeholder="省市区 + 街道门牌"></textarea></div>' +
      '<label class="check-row"><input type="checkbox" id="addr_default"> 设为默认地址</label>' +
      '<button type="button" class="btn-save" onclick="saveAddress()">保存地址</button></div>';
    el.innerHTML = html;
  });
}
function editAddressForm(id) {
  var a = (window.__ADDR_LIST__||[]).filter(function(x){ return x.id===id; })[0]; if(!a) return;
  document.getElementById('addr_id').value = a.id;
  document.getElementById('addr_name').value = a.name||'';
  document.getElementById('addr_phone').value = a.phone||'';
  document.getElementById('addr_detail').value = a.detail||'';
  document.getElementById('addr_default').checked = !!a.is_default;
  document.getElementById('addr-form-title').textContent = '编辑地址';
  document.getElementById('addr-form-box').scrollIntoView({behavior:'smooth'});
}
function saveAddress() {
  var body = { id: document.getElementById('addr_id').value||0, name: document.getElementById('addr_name').value, phone: document.getElementById('addr_phone').value, detail: document.getElementById('addr_detail').value, is_default: document.getElementById('addr_default').checked?1:0 };
  fetch(apiBase+'/address/save.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(body)}).then(function(r){return r.json();}).then(function(j){ showH5Toast(j.message||(j.code===0?'已保存':'失败')); if(j.code===0) loadAddressListPage(); });
}
function deleteAddress(id) {
  if(!confirm('确定删除该地址？')) return;
  fetch(apiBase+'/address/delete.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({id:id})}).then(function(r){return r.json();}).then(function(j){ if(j.code===0) loadAddressListPage(); else showH5Toast(j.message||'失败'); });
}
function setDefaultAddress(id) {
  fetch(apiBase+'/address/set_default.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({id:id})}).then(function(r){return r.json();}).then(function(j){ if(j.code===0) loadAddressListPage(); else showH5Toast(j.message||'失败'); });
}
function renderSettingsPage() {
  setTimeout(loadSettingsPage, 0);
  return renderSubPageNav('设置') + '<div class="sys-page" id="settings-page"><div class="article-empty">加载中...</div></div>';
}
function loadSettingsPage() {
  var el = document.getElementById('settings-page'); if(!el) return;
  fetch(apiBase + '/user/profile.php', { credentials:'same-origin' }).then(function(r){ return r.json(); }).then(function(j){
    if(!j || j.code!==0 || !j.data || !j.data.logged_in){
      el.innerHTML = '<div class="article-empty">未登录</div><div class="sys-menu"><button type="button" onclick="navTo(\'login\')">账号登录</button><button type="button" onclick="navTo(\'register\')">新用户注册</button></div>';
      return;
    }
    var u = j.data.user || {};
    el.innerHTML = '<div class="settings-row"><span>昵称</span><input id="set_nickname" value="'+(u.nickname||'')+'"></div>' +
      '<div class="settings-row"><span>手机号</span><input id="set_phone" value="'+(u.phone||'')+'"></div>' +
      '<div class="settings-row"><span>会员等级</span><span style="color:#999">'+(u.member_level_name||'普通会员')+'</span></div>' +
      '<div class="settings-row"><span>积分</span><span style="color:#999">'+(u.points||0)+'</span></div>' +
      '<div class="sys-menu"><button type="button" onclick="saveProfile()">保存资料</button>' +
      '<button type="button" onclick="navTo(\'address-list\')">收货地址管理</button>' +
      '<button type="button" onclick="navTo(\'' + orderRouteBase() + '\')">我的订单</button>' +
      '<button type="button" onclick="navTo(\'coupon-list\')">我的优惠券</button>' +
      '<button type="button" onclick="doLogout()" style="color:#e74c3c">退出登录</button></div>';
  });
}
function saveProfile() {
  var body = { nickname: document.getElementById('set_nickname').value, phone: document.getElementById('set_phone').value };
  fetch(apiBase+'/user/profile.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(body)}).then(function(r){return r.json();}).then(function(j){ showH5Toast(j.message||(j.code===0?'已保存':'失败')); });
}
function doLogout() {
  fetch(apiBase+'/auth/logout.php',{method:'POST',credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){ window.__USER_LOGGED_IN__=false; showH5Toast(j.message||'已退出'); navTo('mine'); });
}
function renderInvitePage() {
  setTimeout(loadInvitePage, 0);
  return renderSubPageNav('邀请好友') + '<div class="sys-page" id="invite-page"><div class="article-empty">加载中...</div></div>';
}
function loadInvitePage() {
  var el = document.getElementById('invite-page'); if(!el) return;
  fetch(apiBase + '/invite/info.php', { credentials:'same-origin' }).then(function(r){ return r.json(); }).then(function(j){
    if (apiNeedLogin(j)) { el.innerHTML = '<div class="article-empty">'+(j&&j.message||'请先登录')+'</div><p style="text-align:center;margin-top:12px"><button class="btn" onclick="navTo(\'login\')">去登录</button></p>'; return; }
    var d = j.data || {};
    var link = location.href.split('#')[0] + '#register?invite=' + encodeURIComponent(d.invite_code||'');
    var recs = (d.records||[]).map(function(r){
      var name = r.nickname || r.phone || '新用户';
      return '<div class="order-card"><div class="order-card-head"><span>'+name+'</span><span style="color:#2ecc71">+'+(r.points_reward||0)+'积分</span></div><div style="font-size:12px;color:#999">'+(r.created_at||'')+'</div></div>';
    }).join('') || '<div class="article-empty">暂无邀请记录，分享给好友注册即可获得积分</div>';
    el.innerHTML = '<p style="font-size:13px;color:#666;line-height:1.6">邀请好友注册，您得 <strong>'+(d.reward_inviter||50)+'</strong> 积分，好友得 <strong>'+(d.reward_invitee||20)+'</strong> 积分</p>' +
      '<div class="invite-code-box"><div style="font-size:13px;color:#666">我的邀请码</div><div class="code">'+(d.invite_code||'')+'</div>' +
      '<button class="btn" onclick="copyInviteLink(\''+link.replace(/'/g,"\\'")+'\')">复制邀请链接</button></div>' +
      '<div style="display:flex;gap:12px;margin-bottom:12px"><div style="flex:1;text-align:center;background:#f5f5f5;border-radius:8px;padding:12px"><div style="font-size:20px;font-weight:700">'+(d.invite_count||0)+'</div><div style="font-size:12px;color:#999">已邀请</div></div>' +
      '<div style="flex:1;text-align:center;background:#f5f5f5;border-radius:8px;padding:12px"><div style="font-size:20px;font-weight:700">'+(d.invite_points||0)+'</div><div style="font-size:12px;color:#999">累计积分</div></div></div>' +
      '<strong style="font-size:14px">邀请记录</strong>' + recs;
  });
}
function copyInviteLink(link) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(link).then(function(){ showH5Toast('邀请链接已复制'); }).catch(function(){ prompt('请复制邀请链接', link); });
  } else { prompt('请复制邀请链接', link); }
}
function renderCouponListPage() {
  setTimeout(function(){ loadCouponListPage('available'); }, 0);
  return renderSubPageNav('优惠券') + '<div class="sys-page" id="coupon-list-page"><div class="sub-tabs"><button class="sub-tab active" id="cp-tab-available" onclick="loadCouponListPage(\'available\')">可领取</button><button class="sub-tab" id="cp-tab-mine" onclick="loadCouponListPage(\'mine\')">我的券</button></div><div id="coupon-list-body"><div class="article-empty">加载中...</div></div></div>';
}
function loadCouponListPage(tab) {
  tab = tab || 'available';
  var ta = document.getElementById('cp-tab-available'); var tm = document.getElementById('cp-tab-mine');
  if(ta) ta.className = 'sub-tab' + (tab==='available'?' active':'');
  if(tm) tm.className = 'sub-tab' + (tab==='mine'?' active':'');
  var el = document.getElementById('coupon-list-body'); if(!el) return;
  el.innerHTML = '<div class="article-empty">加载中...</div>';
  var url = tab==='mine' ? apiBase+'/coupon/my.php' : apiBase+'/coupon/list.php';
  fetch(url, { credentials:'same-origin' }).then(function(r){ return r.json(); }).then(function(j){
    if (!j || j.code !== 0) { el.innerHTML = '<div class="article-empty">'+(j&&j.message||'加载失败')+'</div>'; return; }
    if (tab === 'mine' && j.data && j.data.logged_in === false) {
      el.innerHTML = '<div class="article-empty">请先登录</div><p style="text-align:center;margin-top:12px"><button class="btn" onclick="navTo(\'login\')">去登录</button></p>';
      return;
    }
    var list = (j.data&&j.data.list)||[];
    if(!list.length){ el.innerHTML = '<div class="article-empty">'+(tab==='mine'?'暂无可用优惠券':'暂无可领取优惠券')+'</div>'; return; }
    el.innerHTML = list.map(function(c){
      var val = '¥'+(c.value||0);
      var meta = '满'+(c.min_amount||0)+'可用' + (c.end_at ? ' · 至'+c.end_at : '');
      var btn = tab==='available' ? '<button class="btn" style="padding:6px 12px;font-size:12px" onclick="receiveCoupon('+c.id+')">领取</button>' : '<span style="font-size:12px;color:#999">可使用</span>';
      return '<div class="coupon-card"><div><div class="coupon-val">'+val+'</div><div style="font-size:14px;font-weight:600">'+(c.name||'优惠券')+'</div><div class="coupon-meta">'+meta+'</div></div>'+btn+'</div>';
    }).join('');
  });
}
function receiveCoupon(id) {
  fetch(apiBase+'/coupon/receive.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({coupon_id:id})}).then(function(r){return r.json();}).then(function(j){ showH5Toast(j.message||(j.code===0?'领取成功':'失败')); if(j.code===0) loadCouponListPage('available'); });
}
function renderMemberCenterPage() {
  setTimeout(loadMemberCenterPage, 0);
  return renderSubPageNav('会员中心') + '<div class="sys-page" id="member-center-page"><div class="article-empty">加载中...</div></div>';
}
function loadMemberCenterPage() {
  var el = document.getElementById('member-center-page'); if(!el) return;
  fetch(apiBase + '/user/center.php', { credentials:'same-origin' }).then(function(r){ return r.json(); }).then(function(j){
    if(!j || j.code!==0){ el.innerHTML = '<div class="article-empty">加载失败</div>'; return; }
    var d = j.data || {}; var u = d.user || {}; var logged = !!d.logged_in;
    var levels = d.member_levels || [];
    var cur = u.member_level || 0;
    var hero = '<div class="member-hero"><div style="font-size:18px;font-weight:700">'+(logged?(u.member_level_name||'普通会员'):'未登录')+'</div>' +
      '<div style="font-size:12px;margin-top:6px;opacity:.85">当前积分 '+(u.points||0)+' · 余额 ¥'+(u.balance||0)+'</div></div>';
    var levelRows = levels.map(function(lv){
      var isCur = cur === lv.id;
      var btn = logged && !isCur ? '<button class="btn" style="padding:4px 10px;font-size:12px" onclick="openVipLevel('+lv.id+')">升级</button>' : (isCur?'<span style="color:#2ecc71;font-size:12px">当前</span>':'');
      return '<div class="member-level-row'+(isCur?' current':'')+'"><div><strong>'+lv.name+'</strong><div style="font-size:12px;color:#999;margin-top:4px">积分≥'+lv.min_points+' · '+(lv.discount<1?((lv.discount*10).toFixed(1)+'折'):'无折扣')+'</div></div>'+btn+'</div>';
    }).join('') || '<div class="article-empty">暂无会员等级配置</div>';
    var benefits = (d.benefits||[]).map(function(b){ return '<span class="uc-benefit-item"><div class="uc-benefit-ico">✨</div>'+b+'</span>'; }).join('');
    el.innerHTML = hero + (logged?'':'<p style="text-align:center;margin-bottom:12px"><button class="btn" onclick="navTo(\'login\')">登录查看会员权益</button></p>') +
      '<strong style="font-size:14px">会员等级</strong>' + levelRows +
      '<div class="uc-benefits" style="margin-top:16px"><div class="uc-benefits-title">会员权益</div><div class="uc-benefit-icons">'+benefits+'</div></div>';
  });
}
async function openVipLevel(levelId) {
  if(!confirm('积分达标将免费升级，否则扣除99元余额开通，确认？')) return;
  var res = await fetch(apiBase + '/user/vip_open.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({level_id:levelId}) });
  var json = await res.json();
  showH5Toast(json.message || (json.code===0 ? '开通成功' : '开通失败'));
  if(json.code===0) loadMemberCenterPage();
}
