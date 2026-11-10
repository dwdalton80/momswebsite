import { original, image, hero, village, stages, picks, stories, activities, videos } from './data.js';
import { startGames } from './games.js';

const main = document.querySelector('main');
const path = location.pathname.split('/').filter(Boolean);
const query = new URLSearchParams(location.search);
const disclosure = 'As an Amazon Associate, WeeTime, MeTime earns from qualifying purchases.';
const photo = (file, alt, w, h, lazy = true) => `<img src="${image(file, w, h)}" alt="${alt}" width="${w}" height="${h}" ${lazy ? 'loading="lazy"' : 'fetchpriority="high"'}>`;
const button = (url, label, style = 'btn') => `<a class="${style}" href="${url}">${label}</a>`;
const title = (label, heading, description = '') => `<div class="section-title"><p class="eyebrow">${label}</p><h2>${heading}</h2>${description ? `<p>${description}</p>` : ''}</div>`;
const intro = (label, heading, description = '') => `<section class="intro"><div class="wrap"><p class="eyebrow">${label}</p><h1>${heading}</h1>${description ? `<p>${description}</p>` : ''}</div></section>`;
const stageUrl = stage => `/shop/?stage=${stage.toLowerCase()}`;
const prices = price => '$' + price.replace('–', '–$');
const saveKey = 'weetime-metime-picks';
let saved = new Set();
try { saved = new Set(JSON.parse(localStorage.getItem(saveKey) || '[]')); } catch {}
const heart = (name, index) => `<button class="heart" data-pick="${index}" aria-label="Save ${name} to Me Time" aria-pressed="${saved.has(index)}">${saved.has(index) ? '♥' : '♡'}</button>`;
const pickCard = ([name, stage, category, price, file, search, description], index) => `<article class="card" data-stage="${stage}" data-category="${category}"><div class="card-media">${photo(file, name, 390, 488)}<span class="tag">${stage}</span>${heart(name,index)}</div><div class="card-body"><span class="meta">${category} · ${prices(price)}</span><h3>${name}</h3><p>${description}</p><a class="text-link" href="https://www.amazon.com/s?k=${encodeURIComponent(search)}&amp;tag=weetime-20" target="_blank" rel="noopener sponsored">View on Amazon ↗</a></div></article>`;
const storyCard = ([slug, category, , name, excerpt, file]) => `<a class="card story" href="/journal/${slug}/">${photo(file, '',390,208)}<div class="card-body"><span class="meta">${category}</span><h3>${name}</h3><p>${excerpt}</p><span class="text-link">Read the story →</span></div></a>`;
const affiliate = `<p class="fine" style="margin-top:1.25rem">${disclosure} <a class="text-link" href="/about/#disclosure">About our links</a></p>`;

function home() {
  return `<section class="hero"><div class="wrap split">
    <div><p class="eyebrow"><span aria-hidden="true">★</span> For the mother you're becoming</p>
    <h1>A softer place to land,<br><em>at every stage</em><br>of motherhood.</h1>
    <p class="hero-copy">Honest product picks, gentle guidance, and a playroom that buys you twenty quiet minutes. Written by moms who are in the thick of it.</p>
    <div class="actions">${button('/shop/','Explore the shop →')}${button('/kids-corner/','Visit the Kids Corner','pill')}</div>
    <dl class="stats"><div><dt>30 days</dt><dd>Min. testing per pick</dd></div><div><dt>4</dt><dd>Life stages covered</dd></div><div><dt>0</dt><dd>Paid placements</dd></div></dl></div>
    <div class="hero-photo">${photo(hero,'A mother holding her newborn in a sunlit living room',557,560,false)}<span class="sticker meta">Mom-approved</span><a class="photo-note" href="${stageUrl('Newborn')}"><span class="eyebrow">Most-loved this week</span><strong>The white noise machine</strong><small>$40–$70 · the gift we bring to every shower</small></a></div>
  </div></section>
  <div class="disclosure">Some links are Amazon affiliate links. <a href="/about/#disclosure">How that works</a></div>
  <section class="section"><div class="wrap">${title('Start where you are', "Pick your season, and we'll narrow the whole world down.", 'Find ideas and honest picks for the stage you’re in.')}
    <div class="grid four">${stages.map(([name, icon, copy]) => `<a class="stage" href="${stageUrl(name)}"><b aria-hidden="true">${icon}</b><h3>${name}</h3><p>${copy}</p><span class="text-link">See the picks →</span></a>`).join('')}</div>
  </div></section>
  <section class="section"><div class="wrap"><div class="section-title row"><div><p class="eyebrow">The Edit</p><h2>The short list, so you're not reading forty reviews at 2am.</h2></div>${button('/shop/','All picks →','pill')}</div><div class="grid">${picks.slice(0,6).map(pickCard).join('')}</div>${affiliate}</div></section>
  <section class="section tint"><div class="wrap split"><div>${title('Kids Corner','Videos, games, and things to do, all in one corner.','Two tap-to-play games, a shelf of videos to watch together, and printable activities for the twenty minutes before dinner.')}${button('/kids-corner/','Open the Kids Corner →')}</div><div class="panel"><a href="/kids-corner/#games"><h3>🧩 Games</h3><p>Match the pairs and pop bubbles.</p></a><a href="/kids-corner/#activities"><h3>🎨 Activities</h3><p>Print an idea with supplies you already have.</p></a><a href="/kids-corner/#videos"><h3>🎬 Videos</h3><p>A short shelf of rhymes and read-alouds.</p></a></div></div></section>
  <section class="section" id="tools"><div class="wrap split"><div>${title('A little help, right now','For the waiting, and the wondering.','Find your estimated due date or sign up for the Sunday Letter through the original WeeTime, MeTime site.')}</div>
  <div class="panel"><form id="due-form"><h3>How far along am I?</h3><label for="last-period">First day of your last period</label><div class="form-row"><input id="last-period" name="date" type="date" required><button class="btn" type="submit">Calculate</button></div><output id="due-result" class="calc-result" aria-live="polite"></output><p class="fine">This estimates 40 weeks from your last period. Ask your provider to confirm your due date. <a class="text-link" href="https://www.acog.org/clinical/clinical-guidance/committee-opinion/articles/2017/05/methods-for-estimating-the-due-date">Dating guidance</a></p></form>
  <hr><h3>The Sunday Letter</h3><p>One honest recommendation, one thing to try with your kid, and a note from someone in the same boat.</p>${button(original+'/#tools','Sign up on the original site ↗','pill')}</div></div></section>
  <section class="section"><div class="wrap"><div class="section-title row"><div><p class="eyebrow">The Journal</p><h2>Written at 3am, edited by daylight.</h2></div>${button('/journal/','Every story →','pill')}</div><div class="grid">${stories.slice(0,3).map(storyCard).join('')}</div></div></section>
  <section class="section blush village"><div class="wrap split">${photo(village,'Three mothers sitting together on a rug while their toddlers play',607,503)}<div>${title('The village','You were never supposed to do this alone.','WeeTime, MeTime started as a group chat between four mothers who kept sending each other the same links at midnight.')}${button('/about/','How we choose →')}</div></div></section>`;
}

function shop() {
  const stage = stages.map(([name])=>name).find(name => name.toLowerCase() === query.get('stage')) || '';
  const categories = ['Sleep','Mama','Travel','Feeding','Play'];
  return intro('The Edit', "Everything we'd buy again. <em>And a lot we wouldn't.</em>", 'Browse by stage or category and save anything you’d like to think about later.') +
    `<section class="section"><div class="wrap"><div class="filters" role="group" aria-label="Life stage">${['',...stages.map(([name])=>name)].map(name => `<a class="filter" ${stage === name ? 'aria-current="page"' : ''} href="${name ? stageUrl(name) : '/shop/'}">${name || 'All stages'}</a>`).join('')}</div>
    <label for="category">Category</label><div class="filters"><select id="category" style="width:auto"><option value="">All categories</option>${categories.map(name=>`<option>${name}</option>`).join('')}</select></div>
    <p id="pick-count" aria-live="polite"></p><div class="grid" id="shop-grid">${picks.map(pickCard).join('')}</div>${affiliate}</div></section>`;
}

function meTime() {
  const cards = picks.map((pick,index) => saved.has(index) ? pickCard(pick,index) : '').join('');
  return intro('Me Time',"The shortlist you're building.",'Tap the heart on any pick and it lands here. Favorites are saved in this browser on this device.') +
    `<section class="section"><div class="wrap" id="saved-picks">${cards ? `<div class="grid">${cards}</div>` : `<div class="panel"><h2>Nothing saved yet.</h2><p>Browse the Edit and tap the heart on anything you want to come back to.</p>${button('/shop/','Browse the Edit →')}</div>`}</div></section>`;
}

function journal() {
  const category = query.get('category') || '';
  return intro('The Journal','Written at 3am, <em>edited by daylight.</em>','The things we wish someone had told us, in the order we needed to hear them.') +
    `<section class="section"><div class="wrap"><div class="filters" aria-label="Journal categories">${['',...stories.map(story=>story[1])].map(name => `<a class="filter" ${category===name?'aria-current="page"':''} href="/journal/${name?'?category='+name:''}">${name || 'All stories'}</a>`).join('')}</div>
    <div class="grid">${stories.filter(story => !stories.some(item=>item[1]===category) || story[1]===category).map(storyCard).join('')}</div></div></section>`;
}

function storyPage([slug,category,,name,excerpt,file,sections]) {
  return intro('The Journal · '+category,name,excerpt) +
    `<article class="section"><div class="wrap"><div class="prose">${photo(file,'',390,208,false)}${sections.map(([heading,paragraph])=>`<h2>${heading}</h2><p>${paragraph}</p>`).join('')}
    <div class="actions">${button(original+'/journal/'+slug,'Read the full original ↗','pill')}${button('/journal/','Back to the Journal','pill')}</div></div></div></article>`;
}

function about() {
  return intro('Our story','It started as a group chat at 2am.','Four mothers, one very long thread of links, and a site we would have wanted to read in the middle of the night.') +
    `<section class="section"><div class="wrap"><div class="prose">${photo(village,'Mothers sitting together on a rug while their toddlers play',607,503)}
    <h2>How a pick makes the Edit</h2><p>One of us buys it and uses it at home for at least a month. If two of us would buy it again, it goes in. If it breaks or gets outgrown in a fortnight, it comes out.</p>
    <h2 id="disclosure">Affiliate disclosure</h2><p>${disclosure} The links cost you nothing extra. Product links open Amazon searches so the picks stay useful when individual listings change.</p>
    <h2 id="privacy">Privacy, simply</h2><p>Me Time favorites are stored in the browser on this device. The games and due-date estimate run in your browser. Video players load from YouTube after you choose a video. The Sunday Letter signup opens the original site.</p>
    ${button('/shop/','Back to the Edit →')}</div></div></section>`;
}

function kids() {
  return intro('Kids Corner','Games, videos and rainy-day things to do.','Two games for small hands, a shelf of videos to watch together, and printables for a rainy afternoon.') +
    `<section class="section" id="games"><div class="wrap">${title('Games','Games, one finger, zero ads.')}<div class="game-row">
    <div class="panel"><div class="game-head"><div><h3>Match the pairs</h3><p>Find all six pairs. Take your time.</p></div><button class="pill" id="restart">Restart</button></div><div id="match-status" role="status"></div><div class="board" id="board"></div></div>
    <div class="panel"><h3>Bubble pop</h3><p>Thirty seconds. Tap every bubble you can.</p><div id="bubble-status">0 popped · 30 seconds left</div><div class="bubble-area" id="bubbles"><button class="btn" id="pop-start">Start popping</button></div><span class="fine">Game scores stay on this page.</span></div>
    </div></div></section>
    <section class="section tint" id="activities"><div class="wrap">${title('Activities','Things to do with what’s already in the house.','Find an idea by age, check the supplies, and print a sheet to keep on the fridge.')}
    <div class="filters" id="age-filters" aria-label="Filter by age">${['','Baby','Toddler','Preschool'].map((age,index)=>`<button class="filter" data-age="${age}" aria-pressed="${index===0}">${age || 'All ages'}</button>`).join('')}</div>
    <div class="grid" id="activity-grid">${activities.map(([slug,icon,name,age,range,time,mess,desc,supplies])=>`<article class="card" data-age-group="${age}"><div class="card-body"><span class="meta">${icon} ${range} · ${time} · ${mess}</span><h3>${name}</h3><p>${desc}</p><details><summary>What you need</summary><p>${supplies}</p></details><a class="text-link" href="/kids-corner/activities/${slug}/">Print sheet →</a></div></article>`).join('')}</div></div></section>
    <section class="section" id="videos"><div class="wrap">${title('Watch together','A small shelf, chosen by hand.','Pick a video when you’re ready to watch together.')}
    <div class="grid">${videos.map(([age,name,channel,id])=>`<article class="card"><button class="video-frame" data-video="${id}" aria-label="Play ${name}">▶</button><div class="card-body"><span class="meta">${age} · ${channel}</span><h3>${name}</h3><a class="text-link" href="https://www.youtube.com/watch?v=${id}" target="_blank" rel="noopener">Open on YouTube ↗</a></div></article>`).join('')}</div></div></section>`;
}

function activityPage([,icon,name,,range,time,mess,desc,supplies,steps]) {
  return intro('Kids Corner · Printable', name, desc) +
    `<section class="section"><div class="wrap"><div class="prose"><span class="meta">${icon} ${range} · ${time} · ${mess}</span><h2>What you need</h2><p>${supplies}</p><h2>How to play</h2><p>${steps}</p><div class="actions no-print activity-print"><button class="btn" id="print-sheet">Print this sheet</button>${button('/kids-corner/#activities','More activities','pill')}</div></div></div></section>`;
}

const pages = { shop, journal, about, 'kids-corner': kids, 'me-time': meTime };
const story = path[0] === 'journal' && stories.find(item => item[0] === path[1]);
const activity = path[0] === 'kids-corner' && path[1] === 'activities' && activities.find(item => item[0] === path[2]);
main.innerHTML = story ? storyPage(story) : activity ? activityPage(activity) : (pages[path[0]] || home)();

if (path.length) document.title = main.querySelector('h1').textContent + ' — WeeTime, MeTime';
document.querySelectorAll('.top a').forEach(link => {
  if (!link.matches('.brand') && link.pathname.split('/')[1] === (path[0] || '')) link.setAttribute('aria-current','page');
});

main.addEventListener('click', event => {
  const toggle = event.target.closest('[data-pick]');
  if (!toggle) return;
  const index = Number(toggle.dataset.pick);
  if (saved.has(index)) saved.delete(index);
  else saved.add(index);
  try { localStorage.setItem(saveKey,JSON.stringify([...saved])); } catch {}
  toggle.setAttribute('aria-pressed',saved.has(index));
  toggle.textContent = saved.has(index) ? '♥' : '♡';
  if (path[0] === 'me-time' && !saved.has(index)) {
    main.innerHTML = meTime();
    main.querySelector('#saved-picks button, #saved-picks a')?.focus();
  }
});

const category = document.querySelector('#category');
if (category) {
  const stage = document.querySelector('[aria-label="Life stage"] [aria-current]')?.textContent;
  const cards = [...document.querySelectorAll('#shop-grid article')];
  const count = document.querySelector('#pick-count');
  function filterPicks() {
    for (const card of cards) card.hidden = (stage !== 'All stages' && card.dataset.stage !== stage) || (!!category.value && category.value !== card.dataset.category);
    const visible = cards.filter(card=>!card.hidden).length;
    count.textContent = visible ? visible + (visible === 1 ? ' pick' : ' picks') : 'No picks match these filters.';
  }
  category.addEventListener('change',filterPicks);
  filterPicks();
}

const dueForm = document.querySelector('#due-form');
if (dueForm) {
  const today = new Date();
  const date = document.querySelector('#last-period');
  const result = document.querySelector('#due-result');
  const day = 86400000;
  const todayUtc = Date.UTC(today.getFullYear(),today.getMonth(),today.getDate());
  date.max = new Date(todayUtc).toISOString().slice(0,10);
  dueForm.addEventListener('submit', event => {
    event.preventDefault();
    const elapsed = (todayUtc - date.valueAsNumber) / day;
    if (!Number.isFinite(elapsed) || elapsed < 0) return;
    if (elapsed > 315) {
      result.textContent = 'Check the date you entered. It is more than 45 weeks ago.';
      return;
    }
    const due = new Intl.DateTimeFormat('en-US',{dateStyle:'long',timeZone:'UTC'}).format(date.valueAsNumber + 280*day);
    const weeks = Math.floor(elapsed/7);
    const trimester = weeks < 14 ? 'First' : weeks < 28 ? 'Second' : 'Third';
    const remaining = 280-elapsed;
    const countdown = remaining > 0 ? remaining+' days until the estimate' : remaining === 0 ? 'estimated due date is today' : Math.abs(remaining)+' days past the estimate';
    result.textContent = `Estimated due date ${due}. ${weeks} weeks, ${elapsed%7} days · ${trimester} trimester · ${countdown}.`;
  });
}

if (document.querySelector('#board')) {
  startGames();
  document.querySelector('#age-filters').addEventListener('click', event => {
    const filter = event.target.closest('button');
    if (!filter) return;
    for (const option of document.querySelectorAll('#age-filters button')) option.setAttribute('aria-pressed',option === filter);
    for (const card of document.querySelectorAll('[data-age-group]')) card.hidden = !!filter.dataset.age && filter.dataset.age !== card.dataset.ageGroup;
  });
  main.addEventListener('click', event => {
    const play = event.target.closest('[data-video]');
    if (!play) return;
    const frame = document.createElement('iframe');
    frame.className = 'video-frame';
    frame.title = play.getAttribute('aria-label');
    frame.src = 'https://www.youtube-nocookie.com/embed/' + play.dataset.video + '?autoplay=1&rel=0';
    frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
    frame.allowFullscreen = true;
    play.replaceWith(frame);
    frame.focus();
  });
}
document.querySelector('#print-sheet')?.addEventListener('click',()=>window.print());
const anchor = location.hash.slice(1);
if (anchor) requestAnimationFrame(()=>document.getElementById(anchor)?.scrollIntoView());
