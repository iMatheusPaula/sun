<script setup lang="ts">
// contact.tpl.php — telefone e nome do autor original trocados por placeholders
import InnerLayout from '@/layouts/InnerLayout.vue'
</script>

<template>
  <InnerLayout location="FALE CONOSCO" subtitle="Fale Conosco">
    <div id="etc" style="margin-bottom: 16px;">
      <div class="quadrosOut">
        <div class="quadros">
          <strong>Visando uma melhor forma para que você possa entrar em contato com os Administradores do Servidor, estamos disponibilizando três maneiras de você entrar em contato com nossa Equipe.</strong>
          <br />
          <br />
          <p class="post-footer"> <strong>1ª - Suporte Via Telefone</strong><br />
            - <strong>Estado</strong>: (00) 0000-0000 – Nome do Administrador<br />
          </p>
          <br />
          <br />
          <p class="post-footer"> <strong>2ª - Suporte Ticket</strong><br />
            Você pode mandar um ticket pelo painel do usuário no site para que nossa equipe solucione o seu problema. </p>
          <br />
          <br />
          <p class="post-footer"> <strong>3ª - Mensagens no Celular do ADM</strong><br />
            Para usar essa opção do site você deve possuir uma conta no servidor.<br />
            <br />
            - <strong>Vá até o painel de controle do site, clique em Suporte, após isso clique em Enviar SMS para os ADMs. Agora siga as instruções que aparecerem na tela.</strong> </p>
        </div>
      </div>
    </div>
  </InnerLayout>
</template>
