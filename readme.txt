=== Virtuaria - Pagseguro para Woocommerce ===
Contributors: tecnologiavirtuaria
Tags: payment, payment method, pagseguro, woocommerce, gateway
Requires at least: 4.7
Tested up to: 6.0.1
Stable tag: 1.1.3
Requires PHP: 7.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Adiciona o Pagseguro como método de pagamento para o Woocommerce.

== Description ==

Utilizando a API mais nova (4.0) de cobrança disponibilizada pelo pagseguro, este plugin tem alta performance para processar seu pagamento e agilizar suas vendas.

* Fácil de instalar e configurar;
* Suporte a crédito e boleto bancário;
* Opção de parcelamento com ou sem juros (configurável no plugin);
* Boleto com prazo de validade configurável;
* Link de segunda via do boleto na tela de confirmação e no e-mail com os detalhes do pedido;
* Cobrança extra;
* Reembolso (total e parcial);
* Opção do cliente salvar método de pagamento (sem armazenar o número do cartão do cliente);
* Checkout Transparente, onde faz o pagamento sem sair do site;
* Relatório (log) para consulta a detalhes de transações, incluindo erros;
* Identificação na fatura para pagamentos via cartão (exibir na fatura);
* Mudança automática dos status dos pedidos (aprovado, negado, cancelado, etc) via Webhook de retorno de dados dos status no Pagseguro;
* Detalhamento nas notas do pedido das operações ocorridas durante a comunicação com o PagSeguro (reembolsos, parcelamentos, mudanças de status e valores recebidos/cobrados);
* PagSeguro Pix;

Com este plugin você poderá fazer reembolsos totais e parciais através da página de gerenciamento do pedido em sua loja.

É disponibilizado ao lojista uma configuração para ativar o “Salvar Método de Pagamento”. Este recurso não armazena os dados do cartão de crédito do comprador, mas sim um código (token) de compra do cartão, o que é suficiente para o cliente realizar compras futuras sem precisar digitar os dados do cartão novamente. 

O plugin conta com a funcionalidade “Cobrança Extra” que permite cobrar um valor extra em pedidos feitos com cartão de crédito. Esta função pode ser útil, por exemplo, para vendas de produtos no peso, pois neste caso o valor final quase sempre é diferente do inicialmente solicitado, algo muito comum em supermercados. Também é útil para os casos onde o cliente solicita a inclusão de novos itens no pedido. Para realizar cobranças extras, é necessário que a função de armazenar dados do pagamento esteja ativa,

[youtube https://www.youtube.com/watch?v=jNu_qX2Od5E]

Observação: [PagSeguro](https://pagseguro.uol.com.br/) é um método de pagamento brasileiro desenvolvido pela UOL. Este plugin foi desenvolvido, sem nenhum incentivo do PagSeguro ou da UOL, a partir da [documentação oficial do PagSeguro](https://dev.pagseguro.uol.com.br/reference/intro-charge) e utiliza a última versão ( 4.0) da API de cobranças. Nenhum dos desenvolvedores deste plugin possui vínculos com o Pagseguro ou UOL.
 
Todas as compras são processadas utilizando o checkout transparente:
- **Transparente:** O cliente faz o pagamento direto no seu site sem precisar ir ao site do PagSeguro.


### PagSeguro Pix ###
* Confirmação automática do pagamento, semelhante a cartão de crédito; 
* Mudança automática dos status dos pedidos (aprovado, negado, cancelado, etc) via Webhook de retorno de dados dos status no Pagseguro;
* Reembolso total e parcial;
* Tempo limite para pagamento configurável;
* Nova cobrança Pix, muito útil para cobrança de valores extras ou nos casos onde o cliente perde o tempo limite de pagamento;
* Pagamento por QR code ou link de pagamento;
* Exibe os dados de pagamento no e-mail enviado e na tela de confirmação do pedido;
* Para mais informações, entre em contato via **tecnologia@virtuaria.com.br**.

[Download Virtuaria PagSeguro pix](https://wordpress.org/plugins/virtuaria-pagseguro-pix/)

**Observação:** Os prints foram feitos em um painel wordpress/woocommerce personalizado pela Virtuaria objetivando otimizar o uso em lojas virtuais, por isso o fundo verde.

**Para mais informações, acesse** [virtuaria.com.br - desenvolvimento de plugins, criação e hospedagem de lojas virtuais](https://virtuaria.com.br/) ou envie um email para tecnologia@virtuaria.com.br

= Compatibilidade =

Este plugin é compatível com o [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/), desta forma é possível enviar os campos de "CPF", "número do endereço" e "bairro".
Compatível com Woocommerce 5.8.0 ou superior

### Descrição em Inglês: ###

Using the newest collection API (4.0) made available by pagseguro, this plugin has high performance to process your payment and speed up your sales.

* Easy to install and configure;
* Credit and bank slip support;
* Option to pay in installments with or without interest (configurable in the plugin);
* Boleto with configurable expiration date;
* Link to the second copy of the ticket on the confirmation screen and in the email with the order details;
* Extra charge;
* Reimbursement (full and partial);
* Save payment method;
* Transparent Checkout;
* Debug for error handling;
* Identification on the invoice for payments via card (display on the invoice);
* Webhook for automatic return of status data (approved, denied, cancelled, etc.);
* PagSeguro Pix.

== Installation ==

= Instalação do plugin: =

* Envie os arquivos do plugin para a pasta wp-content/plugins, ou instale usando o instalador de plugins do WordPress.
* Ative o plugin.
* Navegue para Woocommerce -> Configurações -> Pagamentos, escolha o “Pagseguro” e defina Token e E-mail.

[youtube https://www.youtube.com/watch?v=X1fF3aP34zk]

Para utilizar nosso plugin em produção, é preciso solicitar homologação junto a equipe do pagseguro conforme vídeo abaixo:

[youtube https://www.youtube.com/watch?v=L0iqvf1LL7g]

[Formulário de solicitação de homologação](https://app.pipefy.com/public/form/2e56YZLK)

[youtube https://www.youtube.com/watch?v=BuLa9v-rFvs]

= Requerimentos: =

1- Conta no [PagSeguro](http://pagseguro.uol.com.br/) e ter instalado o [WooCommerce](http://wordpress.org/plugins/woocommerce/);
2 - Plugin [WooCommerce Extra Checkout Fields for Brazil] (http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/).

= Configurações no PagSeguro: =

O token e e-mail devem ser obtidos em seu painel do Pagseguro utilizando o seguinte procedimento:

**Como obter seu token de acesso em Sandbox:**
1. Acesse sua conta de de Sandbox;
2. Localize o menu Perfis de Integração;
3. Clique em Vendedor. O token de sandbox estará disponível na seção Credenciais.
**Como obter seu token de acesso em Produção:**
1. Acesse a sua conta PagSeguro;
2. No menu lateral, selecione Venda online;
3. Vá na opção Integrações;
4. E pressione o botão Gerar Token.

**Apenas com isso já é possível receber os pagamentos e fazer o retorno automático de dados.**

<blockquote>Atenção: Não é necessário configurar qualquer URL em "Página de redirecionamento" ou "Notificação de transação", pois o plugin é capaz de comunicar o PagSeguro pela API quais URLs devem ser utilizadas para cada situação.</blockquote>

= Configurações do Plugin: =

1 - Com o plugin instalado acesse o admin do WordPress e entre em "WooCommerce" > "Configurações" > "Pagamentos" > "PagSeguro".
2 - Adicione o seu e-mail e o token do PagSeguro. 

Pronto, sua loja já pode receber pagamentos pelo PagSeguro.

### Instalação e configuração em Inglês: ###

* Upload plugin files to your plugins folder, or install using WordPress built-in Add New Plugin installer;
* Activate the plugin;
* Navigate to WooCommerce -> Settings -> Payment Gateways, choose PagSeguro and fill in your PagSeguro Email and Token.


== Frequently Asked Questions ==

= Qual é a licença do plugin? =

Este plugin está licenciado como GPLv3.

= O que eu preciso para utilizar este plugin? =

* Ter instalado uma versão atual do plugin WooCommerce.
* Ter instalado uma versão atual do plugin WooCommerce Extra Checkout Fields for Brazil.
* Possuir uma conta no PagSeguro.
* Gerar um token de segurança no PagSeguro.

= PagSeguro recebe pagamentos de quais países? =

No momento o PagSeguro recebe pagamentos apenas do Brasil e utilizando o real como moeda.

Configuramos o plugin para receber pagamentos apenas de usuários que selecionaram o Brasil nas informações de pagamento durante o checkout.

= Quais são os meios de pagamento que o plugin aceita? =

São aceitos pagamentos com cartão de crédito e boleto bancário, entretanto você precisa ativá-los na sua conta.

Confira os [meios de pagamento e parcelamento](https://pagseguro.uol.com.br/para_voce/meios_de_pagamento_e_parcelamento.jhtml#rmcl).

= Como que o plugin faz integração com PagSeguro? =

Fazemos a integração baseada na documentação oficial do PagSeguro que pode ser encontrada nos "[guias de integração](https://dev.pagseguro.uol.com.br/reference/intro-charge)" utilizando a última versão da API de pagamentos.

= É possível enviar os dados de "Número", "Bairro" e "CPF" para o PagSeguro? =

Sim é possível, basta utilizar o plugin "[WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/)".

= O pedido foi pago e ficou com o status de "processando" e não como "concluído", isto está certo? =

Sim, por padrão em compras pagas o status do pedido muda automaticamente para processando, significa que pode enviar sua encomenda. Porém, definir o status como "concluído" é atribuição do lojista ao final do processo de venda.

Para produtos baixáveis a configuração padrão do WooCommerce é permitir o acesso apenas quando o pedido tem o status "Concluído", entretanto nas configurações do WooCommerce na aba *Produtos* é possível ativar a opção **"Conceder acesso para download do produto após o pagamento"** e assim liberar o download quando o status do pedido está como "processando".

Note que caso você esteja utilizando a opção de **sandbox**, é necessário usar um e-mail e token de testes que podem ser encontrados em "[PagSeguro Sandbox > Dados de Teste](https://sandbox.pagseguro.uol.com.br/vendedor/configuracoes.html)".

Se você tem certeza que o Token e E-mail estão corretos você deve acessar a página "WooCommerce > Status do Sistema" e verificar se **fsockopen** e **cURL** estão ativos. É necessário procurar ajuda do seu provedor de hospedagem caso você tenha o **fsockopen** e/ou o **cURL** desativados.

Por último é possível ativar a opção de **Log de depuração** nas configurações do plugin e tentar novamente fechar um pedido (você deve tentar fechar um pedido para que o log grave o erro). Com o log é possível saber exatamente o que está dando errado com a sua instalação.

Caso você não entenda o conteúdo do log não tem problema, você pode me abrir um "[tópico no fórum do plugin](https://wordpress.org/support/plugin/virtuaria-pagseguro#postform)" com o link do log (utilize o [pastebin.com](http://pastebin.com).

= O status do pedido não é alterado automaticamente? =

Sim, o status é alterado automaticamente usando a API de notificações de mudança de status do PagSeguro.

A seguir uma lista de ferramentas que podem estar bloqueando as notificações do PagSeguro:

* Site com CloudFlare, pois por padrão serão bloqueadas quaisquer comunicações de outros servidores com o seu. É possível resolver isso desbloqueando a lista de IPs do PagSeguro.
* Plugin de segurança como o "iThemes Security" com a opção para adicionar a lista do HackRepair.com no .htaccess do site. Acontece que o user-agent do PagSeguro está no meio da lista e vai bloquear qualquer comunicação. Você pode remover isso da lista, basta encontrar onde bloquea o user-agent "jakarta" e deletar ou criar uma regra para aceitar os IPs do PagSeguro).
* `mod_security` habilitado, neste caso vai acontecer igual com o CloudFlare bloqueando qualquer comunicação de outros servidores com o seu. Como solução você pode desativar ou permitir os IPs do PagSeguro.

= Funciona com o Sandbox do PagSeguro? =

Sim, funciona e basta você ativar isso nas opções do plugin, além de configurar o seu "[e-mail e token de testes](https://sandbox.pagseguro.uol.com.br/vendedor/configuracoes.html)".

= Quais URLs eu devo usar para configurar "Notificação de transação" e "Página de redirecionamento"? =

Não é necessário configurar qualquer URL para "Notificação de transação" ou para "Página de redirecionamento", o plugin já diz para o PagSeguro quais URLs serão utilizadas.

= Este plugin permite o reembolso total e parcial da venda? =

Sim, você pode reembolsar pedidos com status processando indo direto a página do pedido no woocommerce e clicar em Reembolso -> Reembolso via Pagseguro e setar o valor seja ele total ou parcial.

= Erro 403 ao utilizar o plugin em produção =

Para utilizar o plugin em produção é preciso solicitar liberação feita pela equipe do PagSeguro. O processo é simples e pode ser feito via "[Solicitar Homologação](https://dev.pagseguro.uol.com.br/reference/request-approval)".


= Quais valores meus clientes podem pagar com este plugin?  =

Não há valores máximos para as vendas, porém existem valores mínimo a serem transacionados com o pagseguro, segue lista:


Método   |   Bandeira   |  Valor Mínimo (R$)  |  Parcela Mínima (R$) 
Crédito  |  Visa              | 1,00          | 5,00
Crédito  |  Mastercard        | 0,20          | 5,00
Crédito  |  American Express  | 0,20          | 5,00
Crédito  |  Demais bandeiras  | 0,20          | 5,00
Boleto   |  –                 | 0,20          | –

### FAQ em Inglês: ###

= What is the plugin license? =

* This plugin is released under a GPLv3 license.

= What is needed to use this plugin? =

* WooCommerce version 4.5 or later installed and active.
* Only one account on [PagSeguro](http://pagseguro.uol.com.br/).

== Screenshots ==

1. Configurações do plugin;
2. Checkout transparente com crédito;
3. Checkout transparente com boleto;
4. Reembolso;
5. Reembolso bem sucedido;
6. Armazenamento dos dados de pagamento;
7. Cobrança adicional;
8. Boleto bancário;
9. Boleto bancário no e-mail de novo pedido.


== Upgrade Notice ==
Nenhuma atualização disponível

== Changelog ==
= 1.0 2022-07-28 =
* Versão inicial.
= 1.0.1 2022-07-29 =
* Tradução do plugin para pt-BR.
= 1.0.2 2022-08-04 =
* Atualizando documentação.
= 1.1.0 2022-09-02 =
* Valor mínimo e início dos juros por parcela.
= 1.1.1 2022-09-08 =
* Criptografia RSA para função crédito.
= 1.1.2 2022-09-13 =
* Máscara para data de validade do cartão.
= 1.1.3 2022-09-15 =
* Validação de campos do crédito.
