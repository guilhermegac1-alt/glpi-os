# glpi-os — Ordem de Serviço para GLPI 11

Plugin para o **GLPI 11** que gera a **Ordem de Serviço em PDF** (A4 ou etiqueta 70×95 mm, com QR Code do chamado) direto da aba **Ordem de Serviço** de cada chamado.

> Port do plugin [OS de Junior Marcati](https://github.com/juniormarcati/os) (v0.2.0-beta7), que funcionava apenas até o GLPI 10.0.x. Licença **GPLv3+**, mantida do projeto original.

**Testado em:** GLPI 11.0.5 · PHP 8.3 · MariaDB 10.11

## Recursos

- O.S. em **A4** ou **etiqueta**, com QR Code que abre o chamado
- Dados da empresa e **logotipo** configuráveis
- **CNPJ por entidade** (aba "Dados para O.S." na entidade)
- Itens vinculados (qualquer tipo de ativo), custos, descrição e solução do chamado
- Controle de acesso por perfil (ver/imprimir e configurar)
- Migração automática dos dados do plugin antigo

## Instalação

1. Baixe o arquivo **`os-glpi11-<versão>.zip`** na página de [Releases](../../releases).
   Não use o botão *Download ZIP* do código-fonte: a pasta precisa se chamar exatamente `os`.
2. Faça backup do banco e da pasta do GLPI.
3. Extraia e envie a pasta `os` para `glpi/plugins/os`.
4. Ajuste o dono dos arquivos: `chown -R www-data:www-data glpi/plugins/os` (use o usuário do seu servidor web).
5. No GLPI: **Configurar > Plug-ins > OS - Ordem de Serviço > Instalar > Ativar**
   (ou `php bin/console plugin:install os -u <usuario>` e `php bin/console plugin:activate os`).
6. Em **Configurar > Plug-ins > OS**, preencha os dados da empresa e envie o logotipo.
7. Em cada **Entidade > aba "Dados para O.S."**, informe o CNPJ do cliente.

## Permissões

Direito **Ordem de Serviço** em *Administração > Perfis > aba "Ordem de Serviço"*:

| Permissão | O que libera |
|---|---|
| Ver / imprimir O.S. | Aba do chamado e PDF (apenas de chamados que o usuário já pode ver) |
| Configurar plugin | Dados da empresa, logotipo e CNPJ das entidades |

Na instalação, perfis que já veem chamados recebem "Ver / imprimir"; perfis que alteram a configuração do GLPI recebem também "Configurar".

## O que mudou em relação ao plugin original

- Compatível com GLPI 11 (estrutura `src/`, namespace `GlpiPlugin\Os`).
- **Segurança:** sem SQL concatenado, login e permissão em todas as páginas, proteção CSRF, saída escapada, upload de logotipo validado e regravado como PNG.
- Configuração em `glpi_configs`; logotipo em `files/_plugins/os/` (a pasta do plugin não precisa de escrita).
- QR Code com a biblioteca do próprio GLPI; FPDF compatível com PHP 8.2+.
- Tabelas em `utf8mb4` e migração automática da versão antiga.
- Solicitante e técnico corretos, solução filtrada por chamado, todos os itens vinculados listados.

## Limitações

- Compatível com GLPI **11.0.x**. Outras versões são recusadas pela checagem do GLPI.
- Textos em português (sem arquivos de tradução).

## Desinstalação

Remove a tabela, a configuração, o direito e o logotipo enviado.

## Contribuindo

Issues e pull requests são bem-vindos. Ao reportar um problema, informe a versão do GLPI, do PHP e o que aparece em `files/_log/php-errors.log`.

## Créditos e licença

- Plugin original: **Junior Marcati** — https://github.com/juniormarcati/os
- Biblioteca [FPDF](http://www.fpdf.org/) (licença própria permissiva, em `lib/fpdf/license.txt`)
- Distribuído sob a **GNU GPL v3 ou posterior** (veja `LICENSE`)
