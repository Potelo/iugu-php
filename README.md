# Iugu para PHP

[![Build status](https://img.shields.io/travis/iugu/iugu-php.svg)](https://travis-ci.org/iugu/iugu-php)

## Requisitos

* PHP 5.4+

## Instalação

Faça o download da biblioteca:

```
git clone https://github.com/iugu/iugu-php
```

Inclua a biblioteca em seu arquivo PHP:

```php
require_once(".../iugu-php/lib/Iugu.php");
```

### Usando Composer

```
$ composer require iugu/iugu
Please provide a version constraint for the iugu/iugu requirement: 1.0.6
```

O autoload do composer irá cuidar do resto.

## Exemplo de Uso

```php
Iugu::setApiKey("c73d49f9-6490-46ee-ba36-dcf69f6334fd"); // Ache sua chave API no Painel

Iugu_Charge::create(
    [
        "token"=> "TOKEN QUE VEIO DO IUGU.JS OU CRIADO VIA BIBLIOTECA",
        "email"=>"your@email.test",
        "items" => [
            [
                "description"=>"Item Teste",
                "quantity"=>"1",
                "price_cents"=>"1000"
            ]
        ]
    ]
);
```

## Modificações deste fork

Este repositório é o fork `Potelo/iugu-php` usado pelo pacote `potelo/multi-payment`. Além do
upstream `iugu/iugu`, ele traz:

- **Cabeçalhos por requisição** (1.1.0): `Iugu_APIRequest::request($method, $url, $data, $headers)`
  aceita uma lista de cabeçalhos no formato `'Nome: valor'`, acrescentada aos padrão. É o que
  permite enviar `Idempotency-Key` nos endpoints que o aceitam (criar fatura, criar assinatura,
  criar cliente e cobrança direta).
- **Status e cabeçalhos da resposta na instância** (1.1.0): depois de cada `request()`,
  `$apiRequest->lastResponseCode` traz o status HTTP (JSON ou não; nulo sem resposta) e
  `$apiRequest->lastResponseHeaders` os cabeçalhos com o nome em minúsculas (`retry-after`, por
  exemplo). A variável global `$iugu_last_api_response_code` continua sendo gravada.
- **Chave de API por instância** (1.1.0): `new Iugu_APIRequest($apiKey)` usa a chave informada;
  sem ela, vale a global de `Iugu::setApiKey()`.
- **Reembolso parcial**: `Iugu_Invoice::refund($partialValueRefundCents)`.
- **Tratamento de erros**: `errors` como objeto ou string é normalizado e a `IuguRequestException`
  carrega o status HTTP em `getCode()` quando a resposta não é JSON.
- **`ca-bundle.crt` atualizado** com os certificados raiz da Mozilla de dezembro de 2025 (o do
  upstream, de 2013, não valida mais o certificado da API).

## Documentação

Acesse [iugu.com/documentacao](http://iugu.com/documentacao) para referência

## Testes

Instale as dependências:

```
composer update
```

Execute a comitiva de testes:

```
composer tests
```

