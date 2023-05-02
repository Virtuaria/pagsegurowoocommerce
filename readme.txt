=== Virtuaria - PagSeguro Crédito, Pix e Boleto ===
Contributors: tecnologiavirtuaria
Tags: payment, payment method, pagseguro, woocommerce, gateway, pix, boleto
Requires at least: 4.7
Tested up to: 6.1.1
Stable tag: 2.2.6
Requires PHP: 7.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Adiciona o PagSeguro como método de pagamento para o Woocommerce.

== Description ==

Fácil de instalar e configurar, permite pagamentos no Cartão de Crédito, Pix e Boleto na sua loja virtual Woocommerce com confirmação automática do pagamento nos 3 métodos. Suporta disparo de cobranças extras, além de reembolso total e parcial. Também permite armazenar método de pagamento para agilizar compras recorrentes.

* Suporte a Crédito, Pix e Boleto Bancário;
* Opção de parcelamento com ou sem juros (configurável no plugin);
* Disparo de cobrança extra;
* Reembolso (total e parcial);
* Modo de processamento (síncrono ou assíncrono) do checkout;
* Opção do cliente salvar método de pagamento (sem armazenar o número do cartão do cliente);
* Boleto com prazo de validade configurável;
* Link de segunda via do boleto na tela de confirmação e no e-mail com os detalhes do pedido;
* Checkout Transparente (permite fazer o pagamento sem sair do site);
* Relatório (log) para consulta a detalhes de transações, incluindo erros;
* Identificação na fatura para pagamentos via cartão (exibir na fatura);
* Mudança automática dos status dos pedidos (aprovado, negado, cancelado, etc) via Webhook de retorno de dados dos status no PagSeguro;
* Detalhamento nas notas do pedido das operações ocorridas durante a comunicação com o PagSeguro (reembolsos, parcelamentos, mudanças de status e valores recebidos/cobrados);
* Permite que a mesma conta do PagSeguro seja usada em várias lojas virtuais diferentes.

[youtube https://www.youtube.com/watch?v=8l3zYtAgG_s&ab_channel=Virtuaria]

### Pix ###
* Confirmação automática do pagamento, semelhante a cartão de crédito; 
* Mudança automática dos status dos pedidos (aprovado, negado, cancelado, etc) via Webhook de retorno de dados dos status no Pagseguro;
* Reembolso total e parcial;
* Tempo limite para pagamento configurável;
* “Nova Cobrança Pix”, muito útil para cobrança de valores extras ou nos casos onde o cliente perde o tempo limite de pagamento;
* Pagamento por QR Code ou link Copia e Cola;
* Exibe os dados de pagamento no e-mail enviado e na tela de confirmação do pedido;
* Desconto percentual configurável para pagamento no Pix.

Atenção: Para vendas com Pix, é necessário que exista uma chave Pix cadastrada na conta do vendedor no painel do PagSeguro. [Mais informações](https://blog.pagseguro.uol.com.br/passo-a-passo-para-cadastrar-sua-chave-aleatoria-e-vender-com-pix-nas-maquininhas-pagseguro/)

### Ativação ###
Este plugin, utiliza a API mais moderna Order/Connect de cobrança disponibilizada pelo pagseguro, o que permite configuração e ativação muito mais simples e segura, sem necessidade de gerar chaves via painel ou chamado junto ao PagSeguro.

### Salvar Método de Pagamento ###
É disponibilizado ao lojista uma configuração para ativar o “Salvar Método de Pagamento”. Este recurso não armazena os dados do cartão de crédito do comprador, mas sim um código (token) de compra do cartão, o que é suficiente para o cliente realizar compras futuras sem precisar digitar os dados do cartão novamente. 

### Cobrança Extra ###
O plugin conta com a funcionalidade “Cobrança Extra” que permite cobrar um valor extra em pedidos feitos com cartão de crédito. Esta função pode ser útil, por exemplo, para vendas de produtos no peso, pois neste caso o valor final quase sempre é diferente do inicialmente solicitado, algo muito comum em supermercados. Também é útil para os casos onde o cliente solicita a inclusão de novos itens no pedido. Para realizar cobranças extras, é necessário que a função de armazenar dados do pagamento esteja ativa.

### Processamento Assíncrono ###
Uma novidade desta versão é o modo de processamento do pedido. Com ele a mudança de status do pedido pode ser realizada em background(Assíncrono), o que confere muito mais rapidez ao checkout.

### Observações: ###
[PagSeguro](https://pagseguro.uol.com.br/) é um método de pagamento brasileiro desenvolvido pela UOL. Este plugin foi desenvolvido, sem nenhum incentivo do PagSeguro ou da UOL, a partir da [documentação oficial do PagSeguro](https://dev.pagseguro.uol.com.br/reference/intro-charge) e utiliza a última versão ( 4.0 ) da API de cobranças. Nenhum dos desenvolvedores deste plugin possui vínculos com o Pagseguro ou UOL.
 
Todas as compras são processadas utilizando o checkout transparente:
- **Transparente:** O cliente faz o pagamento direto no seu site sem precisar ir ao site do PagSeguro.

Os prints foram feitos em um painel wordpress/woocommerce personalizado pela Virtuaria objetivando otimizar o uso em lojas virtuais, por isso o fundo verde, mas o plugin é 100% compatível com o painel padrão do Wordpress.

**Para mais informações, acesse** [virtuaria.com.br - desenvolvimento de plugins, criação e hospedagem de lojas virtuais](https://virtuaria.com.br/) ou envie um email para tecnologia@virtuaria.com.br

Em caso de atualização a partir da versão 1.x, é necessária uma nova autenticação junto ao PagSeguro. O processo está muito mais simples. Favor consultar a aba "Instalação" logo acima para mais detalhes.

= Compatibilidade =

Este plugin necessita do [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/) para funcionar corretamente.
Compatível com Woocommerce 5.8.0 ou superior

= Contribuição =

Se desejar contribuir com o desenvolvimento do plugin, nos envie um pull request no [Github](https://github.com/Virtuaria/pagsegurowoocommerce).


== Installation ==

= Instalação do plugin: =

* Envie os arquivos do plugin para a pasta wp-content/plugins, ou instale usando o instalador de plugins do WordPress.
* Ative o plugin.
* Navegue para Woocommerce -> Configurações -> Pagamentos, escolha o “Pagseguro”,escolha o ambiente (produção ou sandbox), preencha o email da sua conta no PagSeguro e clique em salvar;
* Clique em conectar;
* Conceda as permissões;
* Clique em salvar novamente;

**Apenas com isso já é possível receber os pagamentos e fazer o retorno automático de dados.**

### Atenção:### Para vendas com Pix, é necessário que exista uma chave Pix cadastrada na conta do vendedor no painel do PagSeguro. [Mais informações](https://blog.pagseguro.uol.com.br/passo-a-passo-para-cadastrar-sua-chave-aleatoria-e-vender-com-pix-nas-maquininhas-pagseguro/)


= Requerimentos: =

1- Conta no [PagSeguro](http://pagseguro.uol.com.br/) e ter instalado o [WooCommerce](http://wordpress.org/plugins/woocommerce/);
2 - Plugin [WooCommerce Extra Checkout Fields for Brazil] (http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/).

<blockquote>Atenção: Não é necessário configurar qualquer URL em "Página de redirecionamento" ou "Notificação de transação", pois o plugin trabalha diretamente com a API do PagSeguro.</blockquote>

= Tela de configuração do Plugin: =
Com o plugin instalado acesse o admin do WordPress e entre em "WooCommerce" > "Configurações" > "Pagamentos" > "PagSeguro".

== Frequently Asked Questions ==

= Qual é a licença do plugin? =

Este plugin está licenciado como GPLv3. O código é 100% aberto (Open Source). Não disponibilizamos versões PRO com funcionalidades extras.

= O que eu preciso para utilizar este plugin? =

* Ter instalado uma versão atual do plugin WooCommerce.
* Ter instalado uma versão atual do plugin WooCommerce Extra Checkout Fields for Brazil.
* Possuir uma conta no PagSeguro.
* Caso deseje utilizar pagamentos com Pix, é preciso cadastrar uma chave aleatória em seu painel de vendedor no PagSeguro.

= PagSeguro recebe pagamentos de quais países? =

No momento o PagSeguro recebe pagamentos apenas do Brasil e utilizando o real como moeda.

Configuramos o plugin para receber pagamentos apenas de usuários que selecionaram o Brasil nas informações de pagamento durante o checkout.

= Quais são os meios de pagamento que o plugin aceita? =

São aceitos pagamentos com cartão de crédito, pix e boleto bancário, entretanto você precisa ativá-los na sua conta.

Confira os [meios de pagamento e parcelamento](https://pagseguro.uol.com.br/para_voce/meios_de_pagamento_e_parcelamento.jhtml#rmcl).

= Como que o plugin faz integração com PagSeguro? =

Fazemos a integração baseada na documentação oficial do PagSeguro que pode ser encontrada nos "[guias de integração](https://dev.pagseguro.uol.com.br/reference/order-intro)" utilizando a última versão da API de pagamentos.

= É possível enviar os dados de "Número", "Bairro" e "CPF" para o PagSeguro? =

Sim é possível, basta utilizar o plugin "[WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/)".

= O pedido foi pago e ficou com o status de "processando" e não como "concluído", isto está certo? =

Sim, por padrão em compras pagas o status do pedido muda automaticamente para processando, significa que pode enviar sua encomenda. Porém, definir o status como "concluído" é atribuição do lojista ao final do processo de venda e entrega.

Para produtos baixáveis a configuração padrão do WooCommerce é permitir o acesso apenas quando o pedido tem o status "Concluído", entretanto nas configurações do WooCommerce na aba *Produtos* é possível ativar a opção **"Conceder acesso para download do produto após o pagamento"** e assim liberar o download quando o status do pedido está como "processando".

Note que caso você esteja utilizando a opção de **sandbox**, é necessário usar um e-mail de teste que pode ser encontrado em "[PagSeguro Sandbox > Dados de Teste](https://sandbox.pagseguro.uol.com.br/vendedor/configuracoes.html)".

Se você tem certeza que o E-mail estão corretos você deve acessar a página "WooCommerce > Status do Sistema" e verificar se **fsockopen** e **cURL** estão ativos. É necessário procurar ajuda do seu provedor de hospedagem caso você tenha o **fsockopen** e/ou o **cURL** desativados.

Por último é possível ativar a opção de **Log de depuração** nas configurações do plugin e tentar novamente fechar um pedido (você deve tentar fechar um pedido para que o log grave o erro). Com o log é possível saber exatamente o que está dando errado com a sua instalação.

Caso você não entenda o conteúdo do log não tem problema, você pode me abrir um "[tópico no fórum do plugin](https://wordpress.org/support/plugin/virtuaria-pagseguro#postform)" com o link do log (utilize o [pastebin.com](http://pastebin.com).

= O status do pedido não é alterado automaticamente? =

Sim, o status é alterado automaticamente usando a API de notificações de mudança de status do PagSeguro.

A seguir uma lista de ferramentas que podem estar bloqueando as notificações do PagSeguro:

* Site com CloudFlare, pois por padrão serão bloqueadas quaisquer comunicações de outros servidores com o seu. É possível resolver isso desbloqueando a lista de IPs do PagSeguro.
* Plugin de segurança como o "iThemes Security" com a opção para adicionar a lista do HackRepair.com no .htaccess do site. Acontece que o user-agent do PagSeguro está no meio da lista e vai bloquear qualquer comunicação. Você pode remover isso da lista, basta encontrar onde bloquea o user-agent "jakarta" e deletar ou criar uma regra para aceitar os IPs do PagSeguro).
* `mod_security` habilitado, neste caso vai acontecer igual com o CloudFlare bloqueando qualquer comunicação de outros servidores com o seu. Como solução você pode desativar ou permitir os IPs do PagSeguro.

= Funciona com o Sandbox do PagSeguro? =

Sim, funciona e basta você ativar isso nas opções do plugin, além de configurar o seu "[e-mail de testes](https://sandbox.pagseguro.uol.com.br/vendedor/configuracoes.html)".

= Quais URLs eu devo usar para configurar "Notificação de transação" e "Página de redirecionamento"? =

Não é necessário configurar qualquer URL para "Notificação de transação" ou para "Página de redirecionamento", o plugin já diz para o PagSeguro quais URLs serão utilizadas.

= Este plugin permite o reembolso total e parcial da venda? =

Sim, você pode reembolsar pedidos com status processando indo direto a página do pedido no woocommerce e clicar em Reembolso -> Reembolso via Pagseguro e setar o valor seja ele total ou parcial.

= Dificuldades ao usar a Sandbox =

Em conversa com a equipe de integração do PagSeguro, nos foi informado que a API Orders não é 100% atualizada com a Sandbox. Portanto, é possível que algum dos problemas abaixo aconteça:

* Transação não aparece no painel sandbox apesar de retorno da API correto;
* Notificações de mudança de status não chegam à loja;
* Falha ao reembolsar;
* Dificuldades ao fazer login no painel da sandbox;
* Internal Server Error;
* Transaction is not found;
* Operation timed out;
* Bad Gateway;
* External service error.


= Quais valores meus clientes podem pagar com este plugin?  =

Não há valores máximos para as vendas, porém existem valores mínimo a serem transacionados com o pagseguro, segue lista:


Método   |   Bandeira   |  Valor Mínimo (R$)  |  Parcela Mínima (R$) 
Crédito  |  Visa              | 1,00          | 5,00
Crédito  |  Mastercard        | 0,20          | 5,00
Crédito  |  American Express  | 0,20          | 5,00
Crédito  |  Demais bandeiras  | 0,20          | 5,00
Boleto   |  –                 | 0,20          | –
Pix      |  –                 | 1,00          | –           

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
4. Checkout transparente com pix;
5. Reembolso;
6. Reembolso bem sucedido;
7. Armazenamento dos dados de pagamento;
8. Cobrança adicional;
9. Boleto bancário;
10. Boleto bancário no e-mail de novo pedido;
11. Pagamento com Pix;
12. Segunda via do Pix no e-mail de novo pedido.


== Upgrade Notice ==
Nenhuma atualização disponível

== Changelog ==
= 2.2.6 2023-05-02 =
* Desconto Pix usando total do carrinho sem contar o valor de frete.
* Identificação do titular do cartão no histórico(notas) do pedido.
* Limite de tamanho nos campos de endereço do cliente.
* Otimização na configuração Conectar / Desconectar com o PagSeguro.
= 2.2.5 2023-03-27 =
* Correção do problema ao exibir cobrança adicional em ambientes com php 8.0.
* Correção na apresentação do valor do item no relatório do PagSeguro.
= 2.2.4 2023-03-23 =
* Correção do problema “Pagseguro: must be between 100 and 999999900” em compras no Pix.
= 2.2.3 2023-03-22 =
* Limpar inconsistências do banco de dados quando ocorrer falha ao conectar/desconectar.
= 2.2.2 2023-03-22 =
* Correção na aplicação do desconto Pix no QR Code.
* Prefixo para uso de transações em várias lojas com a mesma conta.
= 2.2.1 2023-03-21 =
* Desconto em pagamentos com Pix.
* Aviso sobre ausência do módulo Brazilian Market on WooCommerce.
= 2.2.0 2023-03-10 =
* Reconhecimento de bandeira de cartão de crédito e exibição de ícone no checkout.
* Ajuste na altura do checkout quando o crédito não está ativo.
* Ao sair do campo “Validade”, no checkout, converter data de expiração de MM/AA para MM/AAAA.
* Nova cobrança Pix - reembolso para primeiro pagamento efetivado e ajuste para prevenir cancelamento do pedido quando a cobrança adicional tiver sido paga.
* Identificação da forma de pagamento na lista de pedidos.
* Melhorias no recebimento de webhooks e notas do histórico do pedido.
= 2.1.0 2023-02-14 = 
* Opção para configurar layout dos campos do checkout (crédito).
* Melhoria no layout da configuração de autorização.
= 2.0.4 2023-02-07 =
* Correção de problema de compatibilidade com php 8.2.
* Compatibilidade com venda para pessoa jurídica(PJ).
* Melhoria no espaçamento do e-mail com pedidos via Pix.
* Campo Bairro (billing_neighborhood) obrigatório.
* Melhoria visual na apresentação do QR code na página de agradecimento do pedido.
* Melhorias visuais no checkout transparente.
= 2.0.3 2023-02-01 =
* Correção na exibição do valor mínimo da parcela.
* Novo campo "Observações" para exibir informações extras abaixo da descrição do método de pagamento.
= 2.0.2 2023-01-26 =
* Melhorando exibição dos meios de pagamento do checkout.
= 2.0.1 2023-01-24 =
* Correção na exibição das abas do checkout.
= 2.0.0 2023-01-23 =
* Suporte a API Orders;
* Suporte a API Connect;
* Pagamento com PIX;
* Modo de processamento Assíncrono;
* Melhorias no histórico de notas e logs do pedido.
= 1.2.0 2022-11-10 =
* Arquivo de homologação gerado automaticamente.
= 1.1.3 2022-09-15 =
* Validação de campos do crédito.
= 1.1.2 2022-09-13 =
* Máscara para data de validade do cartão.
= 1.1.1 2022-09-08 =
* Criptografia RSA para função crédito.
= 1.1.0 2022-09-02 =
* Configuração de valor mínimo e início dos juros por parcela.
= 1.0.2 2022-08-04 =
* Atualizando documentação.
= 1.0.1 2022-07-29 =
* Tradução do plugin para pt-BR.
= 1.0 2022-07-28 =
* Versão inicial.
