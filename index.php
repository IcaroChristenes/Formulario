<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lista de Presença</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body {
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg,#f3e8ff,#dbeafe);
  display:flex;
  align-items:center;
  justify-content:center;
  height:100vh;
}

.card {
  background:#fff;
  padding:30px;
  border-radius:16px;
  width:100%;
  max-width:400px;
  box-shadow:0 20px 40px rgba(0,0,0,0.1);
  text-align:center;
}

input {
  width:80%;
  padding:15px;
  border-radius:10px;
  border:2px solid #ccc;
  margin-top:15px;
}

button {
  width:100%;
  padding:15px;
  border:none;
  border-radius:10px;
  margin-top:15px;
  cursor:pointer;
  font-weight:600;
}

.btn-purple {
  background: linear-gradient(90deg,#9333ea,#7c3aed);
  color:white;
}

.btn-green {
  background:#16a34a;
  color:white;
}

.btn-gray {
  background:#e5e7eb;
}

.flex {
  display:flex;
  gap:10px;
}

/* modal */
.modal {
  position:fixed;
  top:0; left:0;
  width:100%; height:100%;
  background:rgba(0,0,0,0.6);
  display:flex;
  align-items:center;
  justify-content:center;
}

.hidden { display:none; }

.modal-box {
  background:#1f2937;
  color:white;
  padding:25px;
  border-radius:12px;
  text-align:center;
}
</style>
</head>

<body>

<div class="card" id="app"></div>

<!-- Modal -->
<div id="modal" class="modal hidden">
  <div class="modal-box">
    <h3>Presença confirmada!</h3>
    <p id="resumo"></p>
    <button onclick="redirect()">OK</button>
  </div>
</div>

<script>
let step = "input";
let guest = null;
let acompanhantes = [];
let maxAcompanhantes = 0;

/* =====================
   RENDER
===================== */

function render() {
  const app = document.getElementById("app");

  if (step === "input") {
    app.innerHTML = `
      <h2>Boas-vindas</h2>
      <p>Digite seu telefone</p>
      <input id="phone" inputmode="numeric" pattern="[0-9]" placeholder="11 9 9999-9999" oninput="formatPhone(this)">
      <button class="btn-purple" onclick="buscar()">Entrar</button>
    `;
  }

  if (step === "confirm") {
    app.innerHTML = `
      <h2>Olá, ${guest.name}</h2>
      <p>Você confirma presença?</p>

      <div class="flex">
        <button class="btn-green" onclick="confirmar(true)">Sim</button>
        <button class="btn-gray" onclick="confirmar(false)">Não</button>
      </div>
    `;
  }

  if (step === "acompanhantes") {
  app.innerHTML = `
    <h2>Olá, ${guest.name}</h2>

    <div style="background:#eef2ff;padding:15px;border-radius:10px;margin:10px 0;">
  Você pode levar mais <b>${maxAcompanhantes - acompanhantes.length}</b> pessoa(s)<br>
  <small>Adicionados: ${acompanhantes.length} / ${maxAcompanhantes}</small>
</div>

    <input id="nomeAcompanhante" placeholder="Nome do acompanhante">
    
    <button class="btn-purple" onclick="addAcompanhante()"
      ${acompanhantes.length >= maxAcompanhantes ? 'disabled' : ''}>
      Adicionar
    </button>

    <div id="lista"></div>

    <button class="btn-green" onclick="enviar()">Enviar</button>
  `;

  atualizarLista();
}
}

/* =====================
   FUNÇÕES
===================== */

function formatPhone(input) {
  let value = input.value.replace(/\D/g, ''); // remove tudo que não é número

  // limita a 11 dígitos
  value = value.substring(0, 11);

  if (value.length > 2) {
    value = value.replace(/^(\d{2})(\d)/g, "$1 $2");
  }

  if (value.length > 9) {
    value = value.replace(/(\d{5})(\d)/, "$1-$2");
  }

  input.value = value;
}

async function buscar() {
  const phone = document.getElementById("phone").value.replace(/\D/g,'');

  const res = await fetch(`https://formulario-1-ad5k.onrender.com/api/getGuest.php?phone=${phone}`);
  const text = await res.text();
console.log("Resposta do servidor:", text);

const data = JSON.parse(text);

  if (data.guest) {
    guest = data.guest;

    maxAcompanhantes = data.acompanhantes_max || 0;

    // já carregar acompanhantes existentes
    acompanhantes = data.acompanhantes.map(a => a.name);

    step = "confirm";
    render();
  } else {
    alert("Não encontrado");
  }
}

function confirmar(vai) {
  if (vai) {
    step = "acompanhantes";
    render();
  } else {
    acompanhantes = [];
    enviar(false);
  }
}

function addAcompanhante() {
  const nome = document.getElementById("nomeAcompanhante").value.trim();

  if (!nome) return;

  if (acompanhantes.length >= maxAcompanhantes) {
    alert("Limite de acompanhantes atingido!");
    return;
  }

  acompanhantes.push(nome);
  document.getElementById("nomeAcompanhante").value = "";

  atualizarLista();
  render(); // 🔥 atualiza contador e botão
}

function atualizarLista() {
  const lista = document.getElementById("lista");

  lista.innerHTML = acompanhantes.map((a,i) => `
    <p>${a} <button onclick="remover(${i})">❌</button></p>
  `).join("");
}

function remover(i) {
  acompanhantes.splice(i,1);
  atualizarLista();
  render(); // 🔥 atualiza botão novamente
}

/* =====================
   ENVIO (SUPABASE)
===================== */

async function enviar(presenca = true) {
  
  presenca = Boolean(presenca); // garante que seja booleano

  const res = await fetch("https://formulario-1-ad5k.onrender.com/api/confirm.php", {
    method:"POST",
    headers:{"Content-Type":"application/json"},
    body: JSON.stringify({
      phone: guest.phone,
      attending: !!presenca,
      accompanying: acompanhantes
    })
  });

  const text = await res.text();
console.log("Resposta do servidor:", text);

const data = JSON.parse(text);

  if (data.status === "sucesso") {
    mostrarModal();
  } else {
    alert("Erro ao salvar");
  }
}

/* =====================
   MODAL
===================== */

function mostrarModal() {
  document.getElementById("modal").classList.remove("hidden");

  document.getElementById("resumo").innerHTML = `
    Convidado: ${guest.name} <br>
    Acompanhantes: ${acompanhantes.join(", ") || "Nenhum"}
  `;
}

function redirect() {
  window.location.href = "https://casamentodeitaloevivian.netlify.app"; // 🔥 TROCAR DEPOIS
}

/* iniciar */
render();

</script>

</body>
</html>