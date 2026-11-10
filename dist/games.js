export function startGames() {
  const board = document.querySelector('#board');
  const status = document.querySelector('#match-status');
  const animals = ['🐶','🐱','🐭','🦊','🐻','🐸'];
  const names = ['Dog','Cat','Mouse','Fox','Bear','Frog'];
  let deck, open, matched, moves, flipTimer;

  function showStatus() {
    status.textContent = matched === 6 ? `You found all six pairs in ${moves} moves!` : `${moves} moves · ${matched} / 6 pairs`;
  }

  function flip(tile, visible) {
    const animal = deck[Number(tile.dataset.index)];
    tile.textContent = visible ? animals[animal] : '✿';
    tile.setAttribute('aria-label',visible ? names[animal] : 'Hidden card');
    tile.setAttribute('aria-pressed',visible);
  }

  function reset() {
    clearTimeout(flipTimer);
    deck = [0,1,2,3,4,5,0,1,2,3,4,5];
    for (let i = deck.length-1; i > 0; i--) {
      const j = Math.floor(Math.random()*(i+1));
      [deck[i],deck[j]] = [deck[j],deck[i]];
    }
    open = [];
    moves = matched = 0;
    board.innerHTML = deck.map((_,index)=>`<button class="tile" data-index="${index}" aria-label="Hidden card" aria-pressed="false">✿</button>`).join('');
    showStatus();
  }

  board.addEventListener('click',event => {
    const tile = event.target.closest('.tile');
    if (!tile || tile.disabled || open.includes(tile) || open.length === 2) return;
    flip(tile,true);
    open.push(tile);
    if (open.length === 1) return;
    moves++;
    const [first,second] = open;
    if (deck[first.dataset.index] === deck[second.dataset.index]) {
      first.disabled = second.disabled = true;
      matched++;
      open = [];
      (board.querySelector('.tile:not(:disabled)') || document.querySelector('#restart')).focus();
    } else {
      flipTimer = setTimeout(()=>{
        flip(first,false);
        flip(second,false);
        open = [];
      },750);
    }
    showStatus();
  });
  document.querySelector('#restart').addEventListener('click',reset);
  reset();

  const bubbles = document.querySelector('#bubbles');
  const bubbleStatus = document.querySelector('#bubble-status');
  let end, popped, ticker;
  function report() {
    const remaining = Math.max(0,Math.ceil((end-Date.now())/1000));
    bubbleStatus.textContent = `${popped} popped · ${remaining} seconds left`;
    if (!remaining) {
      clearInterval(ticker);
      bubbles.innerHTML = '<button class="btn" id="pop-start">Play again</button>';
      bubbles.firstElementChild.focus();
    }
  }
  function addBubble(slot) {
    const bubble = document.createElement('button');
    bubble.className = 'bubble';
    bubble.setAttribute('aria-label','Pop bubble');
    const column = slot % 4;
    const row = Math.floor(slot/4);
    bubble.style.left = `calc(${column*25 + 6 + Math.random()*5}% - 8px)`;
    bubble.style.top = `${row*32 + 6 + Math.random()*8}%`;
    bubble.dataset.slot = slot;
    bubbles.append(bubble);
    return bubble;
  }
  bubbles.addEventListener('click',event => {
    if (event.target.id === 'pop-start') {
      clearInterval(ticker);
      end = Date.now() + 30000;
      popped = 0;
      bubbles.replaceChildren();
      for (let slot=0;slot<12;slot++) addBubble(slot);
      bubbles.firstElementChild.focus();
      report();
      ticker = setInterval(report,150);
      return;
    }
    const bubble = event.target.closest('.bubble');
    if (!bubble || Date.now() >= end) return;
    popped++;
    const replacement = addBubble(Number(bubble.dataset.slot));
    bubble.remove();
    replacement.focus();
    report();
  });
}
