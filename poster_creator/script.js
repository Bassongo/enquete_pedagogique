const canvas = document.getElementById('posterCanvas');
const ctx = canvas.getContext('2d');
const basePoster = new Image();
basePoster.src = 'poster.png';
let userImg = new Image();
let imgX = 200, imgY = 200; // initial position
let imgScale = 1;
let dragging = false;
let offsetX = 0, offsetY = 0;

function draw() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  if (basePoster.complete) {
    ctx.drawImage(basePoster, 0, 0, canvas.width, canvas.height);
  }
  if (userImg.complete && userImg.src) {
    const w = userImg.width * imgScale;
    const h = userImg.height * imgScale;
    ctx.drawImage(userImg, imgX, imgY, w, h);
  }
}

basePoster.onload = draw;

document.getElementById('photoInput').addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (ev) => {
    userImg = new Image();
    userImg.onload = draw;
    userImg.src = ev.target.result;
  };
  reader.readAsDataURL(file);
});

canvas.addEventListener('mousedown', (e) => {
  if (!userImg.src) return;
  const rect = canvas.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  const w = userImg.width * imgScale;
  const h = userImg.height * imgScale;
  if (x >= imgX && x <= imgX + w && y >= imgY && y <= imgY + h) {
    dragging = true;
    offsetX = x - imgX;
    offsetY = y - imgY;
    canvas.style.cursor = 'grabbing';
  }
});

canvas.addEventListener('mousemove', (e) => {
  if (!dragging) return;
  const rect = canvas.getBoundingClientRect();
  imgX = e.clientX - rect.left - offsetX;
  imgY = e.clientY - rect.top - offsetY;
  draw();
});

canvas.addEventListener('mouseup', () => {
  dragging = false;
  canvas.style.cursor = 'grab';
});

canvas.addEventListener('mouseleave', () => {
  dragging = false;
  canvas.style.cursor = 'grab';
});

document.getElementById('scaleRange').addEventListener('input', (e) => {
  imgScale = parseFloat(e.target.value);
  draw();
});

function downloadCanvas() {
  const link = document.createElement('a');
  link.download = 'affiche.png';
  link.href = canvas.toDataURL('image/png');
  link.click();
}

document.getElementById('downloadBtn').addEventListener('click', downloadCanvas);
