# Virtuaria PagSeguro para Woocommerce

Fácil de instalar e configurar, permite cobrança e reembolso total / parcial no crédito e Pix, além da realização de cobranças extras e emissão de boletos. Também permite armazenar método de pagamento para agilizar compras recorrentes.

## Características
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
* Detalhamento nas notas do pedido das operações ocorridas durante a comunicação com o PagSeguro (reembolsos, parcelamentos, mudanças de status e valores recebidos/cobrados).

## PIX
* Confirmação automática do pagamento, semelhante a cartão de crédito;
* Mudança automática dos status dos pedidos (aprovado, negado, cancelado, etc) via Webhook de retorno de dados dos status no Pagseguro;
Reembolso total e parcial;
* Tempo limite para pagamento configurável;
“Nova Cobrança Pix”, muito útil para cobrança de valores extras ou nos casos onde o cliente perde o tempo limite de pagamento;
Pagamento por QR code ou link de pagamento;
* Exibe os dados de pagamento no e-mail enviado e na tela de confirmação do pedido.

Com este plugin, você poderá fazer reembolsos totais e parciais através da página de gerenciamento do pedido em sua loja.

É disponibilizado ao lojista uma configuração para ativar o “Salvar Método de Pagamento”. Este recurso não armazena os dados do cartão de crédito do comprador, mas sim um código (token) de compra do cartão, o que é suficiente para o cliente realizar compras futuras sem precisar digitar os dados do cartão novamente.

O plugin conta com a funcionalidade “Cobrança Extra” que permite cobrar um valor extra em pedidos feitos com cartão de crédito. Esta função pode ser útil, por exemplo, para vendas de produtos no peso, pois neste caso o valor final quase sempre é diferente do inicialmente solicitado, algo muito comum em supermercados. Também é útil para os casos onde o cliente solicita a inclusão de novos itens no pedido. Para realizar cobranças extras, é necessário que a função de armazenar dados do pagamento esteja ativa.

## Ativação ##
Este plugin, utiliza a API mais moderna Order/Connect de cobrança disponibilizada pelo pagseguro, o que permite configuração e ativação muito mais simples e segura, sem necessidade de gerar chaves via painel ou chamado junto ao PagSeguro.

#### Mais informações em: https://wordpress.org/plugins/virtuaria-pagseguro/
