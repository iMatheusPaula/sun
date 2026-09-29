<script setup lang="ts">
// downloads.tpl.php (ldDownloads: links e tamanhos da settings)
import InnerLayout from '@/layouts/InnerLayout.vue'
import { DOWNLOAD_GAMEFULL_LINK, DOWNLOAD_GAMEFULL_MB, DOWNLOAD_PATCH_LINK, DOWNLOAD_PATCH_MB } from '@/config/settings'
</script>

<template>
  <InnerLayout location="DOWNLOADS" subtitle="Downloads">
    <div id="etc">
      <div class="legend" style="margin-bottom: 25px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Arquivos do Jogo</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <table border="0" width="100%">
            <tbody>
              <tr>
                <td align="center"><strong>Nome</strong></td>
                <td align="center"><strong>Tamanho</strong></td>
                <td align="center"><strong>Descrição</strong></td>
              </tr>
              <tr>
                <td align="center"><a :href="DOWNLOAD_GAMEFULL_LINK"><strong>Jogo Completo</strong></a></td>
                <td align="center">{{ DOWNLOAD_GAMEFULL_MB }}</td>
                <td align="center">Jogo Completo </td>
              </tr>
              <tr>
                <td align="center"><a :href="DOWNLOAD_PATCH_LINK"><strong>Patch Jogo</strong></a></td>
                <td align="center">{{ DOWNLOAD_PATCH_MB }}</td>
                <td align="center">Patch para quem já tem um cliente da mesma versão.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="legend" style="margin-bottom: 25px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Configurações Requeridas</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <table width="100%">
            <tbody>
              <tr>
                <td align="center"><strong>Tipo</strong></td>
                <td align="center"><strong>Minimo Requirido</strong></td>
                <td align="center"><strong>Recomendado</strong></td>
              </tr>
              <tr>
                <td align="center">Processador</td>
                <td align="center">Pentium II - 300 mhz</td>
                <td align="center">Pentium III - 1000 mhz ou superior</td>
              </tr>
              <tr>
                <td align="center">Memória</td>
                <td align="center">128 MB</td>
                <td align="center">512 MB ou superior</td>
              </tr>
              <tr>
                <td align="center">Placa de Video</td>
                <td align="center">16 MB</td>
                <td align="center">64 MB ou superior</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="legend" style="margin-bottom: 10px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Utilitários</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <table width="100%">
            <tbody>
              <tr>
                <td align="center"><strong>Programa</strong></td>
                <td align="center"><strong>Descrição</strong></td>
              </tr>
              <tr>
                <td align="center"><a href="http://baixaki.ig.com.br/download/DirectX.htm" target="_blank">DirectX 9.0c</a></td>
                <td align="center">Os Drivers do DirectX são pré-requisitos obrigatórios para quem tem ou pretende ter games no PC.</td>
              </tr>
              <tr>
                <td align="center"><a href="http://baixaki.ig.com.br/download/Spybot-Search-Destroy.htm" target="_blank">Spybot</a></td>
                <td align="center">Remova programas espiões que você nem imagina existirem no seu computador.</td>
              </tr>
              <tr>
                <td align="center"><a href="http://baixaki.ig.com.br/download/AVG-Anti-Virus-Free.htm" target="_blank">AVG</a></td>
                <td align="center">Excelente antivírus grátis, com atualizações via web e tudo mais!</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </InnerLayout>
</template>
