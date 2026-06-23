# TERMOS DE USO E CONTRATO DE PRESTAÇÃO DE SERVIÇO — SAAS MESA

## 1. IDENTIFICAÇÃO DAS PARTES

**1.1. OPERADOR:**
[RAZÃO SOCIAL DO OPERADOR], pessoa jurídica de direito privado, inscrita no CNPJ/CPF sob o nº [CNPJ/CPF], com sede na [ENDEREÇO COMPLETO], doravante denominado simplesmente **Operador** ou **Plataforma**.

**1.2. CONTRATANTE:**
A pessoa física ou jurídica que, mediante cadastro eletrônico na plataforma SaaS Mesa, adere aos presentes Termos de Uso e Contrato de Prestação de Serviço, doravante denominado **Contratante** ou **Tenant**. O Contratante declara ser o responsável legal pelo estabelecimento comercial (restaurante, bar, lanchonete, pizzaria, sorveteria, padaria, rotisseria, food truck ou qualquer outro estabelecimento do ramo alimentício) e utiliza a plataforma para gestão operacional, comercial e financeira do seu negócio.

**1.3. VINCULAÇÃO:**
A simples realização do cadastro eletrônico, a contratação de qualquer plano, a utilização de quaisquer funcionalidades ou o mero acesso à plataforma SaaS Mesa importa na automática e irretratável adesão do Contratante a todos os termos e condições aqui estabelecidos, vinculando as Partes desde a data do primeiro acesso.

## 2. DEFINIÇÕES

Para os fins deste instrumento, os termos a seguir, utilizados em letra maiúscula ou minúscula, terão os significados que lhes são atribuídos nesta cláusula, tanto no singular quanto no plural:

**2.1. SaaS Mesa:** Plataforma de software como serviço (Software as a Service) de gestão de restaurantes, operada pelo Operador, acessível via navegador web (painel administrativo e painel do cliente) e via aplicações mobile, composta por módulos de gestão de mesas, cardápio digital, processamento de pedidos, delivery, gestão de entregadores, pagamentos eletrônicos, cupons de desconto, relatórios financeiros e demais funcionalidades correlatas. A plataforma é arquitetada sob regime multi-tenant, em que cada Contratante opera em ambiente logicamente isolado, compartilhando a mesma infraestrutura de servidores, banco de dados e aplicação, mas com isolamento completo de dados mediante escopo global de inquilino (TenantScope) aplicado a todos os modelos de dados.

**2.2. Tenant / Estabelecimento:** Cada Contratante da plataforma SaaS Mesa, correspondente a um estabelecimento comercial individual. Cada Tenant possui registro próprio na base de dados, identificado por campos como nome, e-mail, slug (identificador único utilizado em subdomínios), domínio personalizado, número de WhatsApp, logotipo, horários de funcionamento, configurações SMTP próprias e credenciais de pagamento EfiBank. O Tenant pode possuir múltiplos Usuários Administradores e Atendentes vinculados.

**2.3. Usuário Administrador (role *admin*):** Usuário com papel (*role*) de administrador no sistema, conforme definido no modelo de Usuário da plataforma. É a pessoa natural indicada pelo Contratante como responsável pela gestão completa do estabelecimento na plataforma, com poderes para cadastrar cardápios, gerenciar mesas, visualizar relatórios financeiros, emitir relatórios, configurar meios de pagamento e gerenciar demais usuários (atendentes, entregadores). Corresponde ao papel `admin` no sistema de autenticação multi-função da plataforma.

**2.4. Atendente (role *atendente*):** Usuário com papel (*role*) de atendente, vinculado ao Tenant, com permissões limitadas para registrar pedidos em nome de clientes, consultar o cardápio, alterar status de pedidos dentro do fluxo operacional (novo, em preparo, pronto, saiu para entrega, entregue, cancelado, fechado) e gerenciar o salão. Não possui acesso a relatórios financeiros, configurações de pagamento ou dados cadastrais do Tenant.

**2.5. Cliente Final (role *cliente*):** Usuário com papel (*role*) de cliente, que é o consumidor final do estabelecimento do Contratante. Este usuário acessa o cardápio digital, realiza pedidos, consulta o status de seus pedidos e efetua pagamentos. O Cliente Final não é parte deste Contrato — sua relação jurídica é exclusivamente com o Contratante, sendo o Operador mero fornecedor de ferramenta tecnológica para viabilizar a interação entre o Contratante e seus consumidores.

**2.6. Entregador (*DeliveryPerson*):** Prestador de serviço cadastrado pelo Contratante na plataforma, responsável pela realização de entregas de pedidos do tipo "entrega". Cada Entregador possui token de autenticação próprio (*api_token*) armazenado de forma segura no banco de dados, permitindo acesso à API REST mobile para visualização de pedidos disponíveis, aceite de corridas e atualização de status (saiu_entrega, entregue). O Entregador é vinculado exclusivamente a um Tenant e não possui acesso ao painel administrativo web.

**2.7. Pedido (*Order*):** Registro eletrônico de uma solicitação de compra realizada por um Cliente Final no estabelecimento do Contratante. Cada Pedido contém: identificador único (UUID), itens consumidos, valor total, método de pagamento, status no fluxo operacional (novo, em_preparo, pronto, saiu_entrega, entregue, cancelado, fechado), tipo (mesa/entrega/retirada), endereço de entrega (armazenado em campo `address_json`), dados de contato do cliente, cupom de desconto aplicado (se houver) e informações de rastreamento de pagamento. Pedidos do tipo "mesa" referem-se a consumo no salão; "entrega" a delivery; "retirada" a pedidos para buscar no local.

**2.8. Credenciais EfiBank do Tenant:** Conjunto de credenciais de autenticação para integração com a plataforma de pagamentos EfiBank (arranjo de pagamento regulado pelo Banco Central do Brasil), compostas por: *client_id*, *client_secret*, *PIX key* (chave PIX), certificado digital no formato `.p12` e senha do certificado. Estas credenciais são de propriedade e responsabilidade exclusiva do Contratante, que as obtém diretamente junto à EfiBank. O Operador as armazena de forma criptografada no banco de dados utilizando o algoritmo AES-256-GCM, com chave de criptografia derivada de `TENANT_CREDENTIAL_ENCRYPTION_KEY` via HKDF-SHA256. As credenciais são utilizadas exclusivamente para processamento de transações PIX do próprio estabelecimento do Contratante com seus Clientes Finais.

**2.9. Plano Gratuito:** Modalidade de contratação sem custo mensal, limitada a até 2 (duas) mesas e recursos restritos (ocultação automática de mesas excedentes, funcionalidades reduzidas). O Tenant em Plano Gratuito não possui acesso a recursos premium como relatórios financeiros avançados, múltiplos entregadores ou suporte prioritário.

**2.10. Plano Premium:** Modalidade de contratação mediante pagamento de assinatura mensal, permitindo até 50 (cinquenta) mesas e acesso completo a todas as funcionalidades da plataforma, incluindo múltiplos entregadores, relatórios financeiros, integração completa com EfiBank e suporte técnico prioritário. O valor mensal do Plano Premium é de R$ 97,90 (noventa e sete reais e noventa centavos), podendo ser contratado com descontos progressivos por períodos de múltiplos meses.

**2.11. Período de Trial (*Trial*):** Período de avaliação gratuita da plataforma, com duração de 7 (sete) dias corridos contados da data de criação da assinatura do Tenant. Durante o período de Trial, o Contratante tem acesso a todas as funcionalidades do Plano Premium, sem qualquer ônus. O término do Trial é registrado no campo `trial_ends_at` do modelo Tenant e no campo homônimo do modelo SaasSubscription.

**2.12. Webhook:** Mecanismo de comunicação assíncrona entre sistemas, pelo qual a EfiBank notifica o Operador sobre eventos relacionados a transações PIX (confirmação de pagamento, falha na cobrança, reembolso). O Operador disponibiliza endpoints seguros protegidos por validação de assinatura HMAC-SHA256 (cabeçalho `x-efi-hmac-sha256`) e verificação de IP de origem contra lista de IPs conhecidos da EfiBank. Os webhooks são processados de forma assíncrona através de filas gerenciadas pelo Supervisor (filas Redis + PHP), com registro completo do payload, assinatura e status de processamento na tabela `webhook_logs`. O Operador processa dois tipos de webhook: webhook SaaS (para cobranças de assinatura do próprio Tenant) e webhook do Tenant (para pagamentos PIX realizados pelos Clientes Finais do estabelecimento).

**2.13. Multi-tenancy / Isolamento de Dados:** Arquitetura de software na qual uma única instância da aplicação atende a múltiplos Tenants (inquilinos), com isolamento lógico completo de dados. No SaaS Mesa, o isolamento é implementado mediante *Global Eloquent Scope* (TenantScope) que aplica automática e obrigatoriamente o filtro `tenant_id` em todas as consultas ao banco de dados nos modelos que armazenam dados específicos de cada Tenant (Order, Payment, DeliveryPerson, TenantEfiCredentials, TenantBillingConfig). Cada Tenant visualiza e opera exclusivamente sobre seus próprios dados, sendo tecnicamente impossível, no curso normal da operação do sistema, que um Tenant acesse dados de outro.

## 3. OBJETO DO CONTRATO

**3.1.** O presente contrato tem por objeto a cessão de uso, em caráter não exclusivo, não transferível e não sublicenciável, da plataforma SaaS Mesa, mediante acesso remoto via internet, para gestão operacional do estabelecimento comercial do Contratante.

**3.2.** A plataforma SaaS Mesa compreende os seguintes módulos e funcionalidades:

**3.2.1. Gerenciamento de Mesas:** Cadastro, edição, organização espacial e controle de ocupação de mesas do estabelecimento, com geração de QR Code individual por mesa para acesso ao cardápio digital pelos Clientes Finais. A quantidade máxima de mesas ativas varia conforme o plano contratado.

**3.2.2. Cardápio Digital:** Publicação de cardápio eletrônico acessível via navegador web, com categorias, descrições, preços, fotos e informações nutricionais. O cardápio é dinâmico e reflete em tempo real as alterações realizadas pelo Contratante.

**3.2.3. Processamento de Pedidos:** Fluxo completo de registro e acompanhamento de pedidos, abrangendo as modalidades mesa (consumo no salão), entrega (delivery) e retirada. O sistema gerencia automaticamente a transição de status do pedido (novo, em_preparo, pronto, saiu_entrega, entregue, cancelado, fechado), notificando as partes envolvidas conforme o andamento.

**3.2.4. Gestão de Entregadores:** Cadastro e gerenciamento de entregadores, com aplicação mobile própria (API REST autenticada por token) para que estes visualizem pedidos disponíveis, aceitem corridas e atualizem status de entrega.

**3.2.5. Pagamentos PIX via EfiBank do Contratante:** Integração com a plataforma EfiBank para processamento de pagamentos PIX realizados pelos Clientes Finais do estabelecimento. As transações são processadas exclusivamente com as credenciais EfiBank do próprio Contratante, que são armazenadas de forma criptografada no banco de dados. O Operador não processa, retém, intermedeia ou tem acesso aos valores transacionados entre o Contratante e seus Clientes Finais.

**3.2.6. Cupons de Desconto:** Criação e gerenciamento de cupons de desconto pelo Contratante para aplicação em pedidos de seus Clientes Finais.

**3.2.7. Relatórios Financeiros:** Geração de relatórios de vendas, faturamento, formas de pagamento, desempenho por período e extrato de transações.

**3.2.8. Painel Web e API Mobile:** Acesso via painel administrativo web para gestão completa do estabelecimento, e API REST para integração com aplicações mobile dos Entregadores.

**3.3.** O Operador se obriga a disponibilizar a plataforma conforme descrito neste instrumento, podendo, a seu exclusivo critério, adicionar, modificar ou descontinuar funcionalidades, desde que não prejudique substancialmente o objeto contratual, mediante comunicação prévia ao Contratante com antecedência mínima de 15 (quinze) dias.

## 4. PLANOS, PREÇOS E CONDIÇÕES DE PAGAMENTO

**4.1. PLANO GRATUITO:**

**4.1.1.** O Plano Gratuito é oferecido sem qualquer custo de assinatura ao Contratante, permitindo o uso limitado da plataforma com as seguintes restrições:
- Limite máximo de 2 (duas) mesas simultaneamente ativas;
- Ocultação automática das mesas excedentes ao limite;
- Recursos limitados, conforme definidos na tabela de funcionalidades disponível na plataforma;
- Ausência de suporte técnico prioritário.

**4.1.2.** O Operador reserva-se o direito de, a qualquer tempo e mediante aviso prévio de 15 (quinze) dias, alterar as condições do Plano Gratuito, incluindo a sua descontinuação.

**4.2. PLANO PREMIUM:**

**4.2.1.** O Plano Premium é contratado mediante pagamento de assinatura mensal no valor de R$ 97,90 (noventa e sete reais e noventa centavos), que dá direito a:
- Até 50 (cinquenta) mesas simultaneamente ativas;
- Acesso completo a todas as funcionalidades da plataforma, incluindo relatórios financeiros, múltiplos entregadores e integração completa com EfiBank;
- Suporte técnico prioritário.

**4.2.2.** O Contratante poderá optar pela contratação antecipada de múltiplos meses, hipótese em que fará jus aos seguintes descontos progressivos sobre o valor mensal de R$ 97,90:

| Período | Desconto | Valor Mensal Efetivo |
|---|---|---|
| 1 (um) mês | 0% (zero por cento) | R$ 97,90 |
| 3 (três) meses | 15% (quinze por cento) | R$ 83,22 |
| 6 (seis) meses | 23% (vinte e três por cento) | R$ 75,38 |
| 12 (doze) meses | 32% (trinta e dois por cento) | R$ 66,57 |

**4.2.3.** A contratação por múltiplos meses implica o pagamento integral e antecipado do valor total correspondente ao período escolhido, calculado pela fórmula: valor mensal × (1 − desconto_percentual/100) × número_de_meses.

**4.3. COBRANÇA E FORMA DE PAGAMENTO:**

**4.3.1.** A cobrança da assinatura do Plano Premium é realizada exclusivamente por meio de PIX (Pagamento Instantâneo), regulado pelo Banco Central do Brasil, processado em nome do Operador através da conta EfiBank do Operador.

**4.3.2.** Para cada ciclo de cobrança, o Operador gera um PIX com valor correspondente ao plano contratado, endereçado à chave PIX do Operador. A confirmação do pagamento é processada de forma automática e assíncrona via webhook de confirmação enviado pela EfiBank ao endpoint seguro do Operador, que valida a autenticidade da notificação mediante assinatura HMAC-SHA256 e verificação de IP de origem.

**4.3.3.** O prazo de compensação do PIX é de até 5 (cinco) minutos em condições normais de funcionamento, podendo ser superior em caso de indisponibilidade temporária do sistema de pagamentos instantâneos (SPI) do Banco Central ou falhas na infraestrutura da EfiBank.

**4.3.4.** Cada cobrança PIX gerada possui prazo de validade de 1 (uma) hora, conforme parâmetro `expiracao` de 3600 (três mil e seiscentos) segundos configurado na integração com a EfiBank. Caso o PIX expire sem pagamento, o Operador poderá gerar novo PIX para cobrança, repetindo-se o procedimento até o adimplemento ou a suspensão do acesso.

**4.4. REAJUSTE:**

**4.4.1.** O valor da assinatura do Plano Premium poderá ser reajustado anualmente pelo Operador, mediante comunicação ao Contratante com antecedência mínima de 30 (trinta) dias.

**4.4.2.** O reajuste será calculado com base na variação do Índice Nacional de Preços ao Consumidor Amplo (IPCA) acumulado nos 12 (doze) meses anteriores, ou, na falta deste, por índice oficial que vier a substituí-lo, ou, ainda, por percentual a ser livremente definido pelo Operador, desde que não superior à variação do IPCA no período.

**4.4.3.** O Contratante que não concordar com o reajuste poderá rescindir o contrato sem penalidades, desde que manifeste sua oposição por escrito no prazo de 15 (quinze) dias contados do recebimento da comunicação de reajuste.

## 5. PERÍODO DE TRIAL E SUSPENSÃO

**5.1. PERÍODO DE TRIAL GRATUITO:**

**5.1.1.** Ao realizar o cadastro na plataforma, o Contratante recebe automaticamente um período de avaliação gratuita (*trial*) de 7 (sete) dias corridos, durante o qual poderá utilizar todas as funcionalidades do Plano Premium sem qualquer ônus.

**5.1.2.** O início do período de trial é contado da data de criação da assinatura do Tenant, registrada no campo `trial_ends_at` do modelo Tenant e no campo `trial_ends_at` do modelo SaasSubscription, ambos no banco de dados da plataforma.

**5.1.3.** Durante o período de trial, o Contratante não está obrigado ao pagamento de qualquer valor, mas deverá, caso deseje continuar utilizando a plataforma após o término do trial, contratar um dos planos disponíveis.

**5.2. TÉRMINO DO PERÍODO DE TRIAL:**

**5.2.1.** Esgotado o período de trial sem que o Contratante tenha contratado qualquer plano, o acesso do Tenant às funcionalidades da plataforma será automaticamente restrito ao Plano Gratuito, com as limitações previstas na Cláusula 4.1.

**5.2.2.** Caso o Contratante não tenha contratado qualquer plano e o período de trial tenha expirado, os dados do Tenant permanecerão armazenados na plataforma pelo prazo de 30 (trinta) dias, após o qual poderão ser definitivamente excluídos, observado o disposto na Cláusula 12.

**5.3. SUSPENSÃO ADMINISTRATIVA POR INADIMPLÊNCIA:**

**5.3.1.** O não pagamento da assinatura do Plano Premium no vencimento importará na alteração do status da assinatura para "past_due" (vencida), momento a partir do qual o Contratante terá o prazo de 5 (cinco) dias corridos para regularizar o pagamento, conforme configurado em `EFI_SUSPENSION_AFTER_DAYS`.

**5.3.2.** Esgotado o prazo previsto na Cláusula 5.3.1 sem que o pagamento seja confirmado pelo webhook da EfiBank, o Operador procederá à **suspensão automática** do Tenant, mediante:
- Alteração do status do Tenant para "suspended" no banco de dados;
- Bloqueio imediato do acesso a todas as funcionalidades da plataforma, tanto pelo painel web quanto pela API mobile;
- Exibição de mensagem de pagamento pendente nos endpoints da plataforma.

**5.3.3.** A suspensão poderá ser aplicada administrativamente pelo superadmin, a qualquer momento, inclusive antes do prazo de 5 (cinco) dias, em caso de indícios de fraude, uso indevido ou violação grave dos termos deste contrato, mediante o endpoint `POST /api/superadmin/tenants/{tenant}/suspend`.

**5.3.4.** Durante o período de suspensão, os dados do Contratante permanecem armazenados na plataforma, mas inacessíveis ao Contratante e a seus usuários.

**5.4. REATIVAÇÃO:**

**5.4.1.** A reativação do acesso ocorrerá automaticamente assim que o pagamento em aberto for confirmado pelo webhook da EfiBank e processado pelo serviço `ProcessEfiBankWebhook`, que alterará o status da assinatura para "active" e o status do Tenant para "active".

**5.4.2.** A reativação também poderá ocorrer mediante pagamento realizado fora do fluxo de webhook, caso em que o Operador, verificando o crédito em sua conta, procederá manualmente à reativação ou, alternativamente, o comando programado `saas:check-subscriptions` (executado a cada hora) verificará junto à EfiBank a situação dos pagamentos e reativará os Tenants elegíveis.

## 6. OBRIGAÇÕES DO OPERADOR

**6.1. DISPONIBILIDADE DO SERVIÇO (SLA):**

**6.1.1.** O Operador envidará seus melhores esforços para manter a plataforma SaaS Mesa disponível 99% (noventa e nove por cento) do tempo mensal, excluídas as indisponibilidades decorrentes de:
- Manutenções programadas, comunicadas com antecedência mínima de 48 (quarenta e oito) horas;
- Caso fortuito ou força maior, assim reconhecidos pela jurisprudência brasileira;
- Falhas de infraestrutura de terceiros (serviços de nuvem, provedores de internet, sistemas de pagamento);
- Ataques cibernéticos, desde que o Operador tenha adotado medidas de segurança razoáveis;
- Ações ou omissões do Contratante ou de seus usuários.

**6.1.2.** O cálculo da disponibilidade será apurado com base em métricas internas do Operador, considerando o período de 30 (trinta) dias corridos.

**6.2. MANUTENÇÃO DE BACKUPS:**

**6.2.1.** O Operador realiza backups automáticos e periódicos do banco de dados da plataforma (MySQL 8+), com frequência mínima diária, armazenados em local seguro e segregado do ambiente de produção.

**6.2.2.** O Operador não se responsabiliza pela perda de dados decorrente de ação ou omissão do Contratante, incluindo, mas não se limitando a, exclusão acidental de dados, alterações indevidas no sistema ou falhas na configuração de integrações.

**6.3. SEGURANÇA DOS DADOS:**

**6.3.1.** O Operador adota as seguintes medidas de segurança da informação para proteção dos dados do Contratante:
- Criptografia AES-256-GCM em repouso para credenciais EfiBank dos Tenants, com chave de criptografia dedicada (`TENANT_CREDENTIAL_ENCRYPTION_KEY`);
- Hash seguro de senhas mediante algoritmo bcrypt;
- TLS/SSL para todas as comunicações de rede;
- Validação de assinatura HMAC-SHA256 em webhooks;
- Verificação de IP de origem para notificações EfiBank;
- Rate limiting (throttle) de 10 (dez) requisições por minuto no login e 5 (cinco) requisições por minuto na API de autenticação;
- Cabeçalhos de segurança HTTP (X-Frame-Options, Content-Security-Policy, X-Content-Type-Options, Referrer-Policy, HSTS em produção).

**6.4. ISOLAMENTO MULTI-TENANT:**

**6.4.1.** O Operador garante que a arquitetura da plataforma implementa isolamento lógico de dados entre Tenants mediante Global Eloquent Scope (TenantScope), que aplica automaticamente o filtro `tenant_id` em todas as consultas ao banco de dados nos modelos de dados segregados (Order, Payment, DeliveryPerson, TenantEfiCredentials, TenantBillingConfig). Este isolamento impede, no curso normal da operação do sistema, que dados de um Tenant sejam acessíveis por outro.

**6.5. SUPORTE TÉCNICO:**

**6.5.1.** O Operador disponibilizará canais de suporte técnico para atendimento ao Contratante, incluindo:
- Canal de e-mail para abertura de chamados;
- Base de conhecimento e documentação online;
- Suporte prioritário para Contratantes do Plano Premium.

**6.5.2.** O prazo de resposta para chamados técnicos será de até 48 (quarenta e oito) horas úteis para Contratantes do Plano Premium e de até 120 (cento e vinte) horas úteis para Contratantes do Plano Gratuito.

**6.6. MANUTENÇÕES PROGRAMADAS:**

**6.6.1.** O Operador comunicará ao Contratante, com antecedência mínima de 48 (quarenta e oito) horas, a realização de manutenções programadas que possam implicar indisponibilidade da plataforma, preferencialmente em horários de baixa atividade.

**6.7. PROCESSAMENTO DE WEBHOOKS:**

**6.7.1.** O Operador garante o processamento assíncrono dos webhooks de eventos EfiBank mediante filas gerenciadas pelo Supervisor (filas Redis + PHP), com registro completo na tabela `webhook_logs`. O processamento inclui:
- Para webhooks SaaS: atualização do status da assinatura do Contratante e reativação automática em caso de confirmação de pagamento;
- Para webhooks do Tenant: atualização do status de pagamento do pedido e notificação em tempo real via eventos broadcast.

**6.8. LIMITAÇÃO DE RESPONSABILIDADE SOBRE TERCEIROS:**

**6.8.1.** O Operador não se responsabiliza por:
- Falhas, indisponibilidades, atrasos ou erros decorrentes da EfiBank, do Sistema de Pagamentos Instantâneos (SPI) do Banco Central do Brasil, de operadoras de cartão de crédito/débito, ou de quaisquer outros intermediários financeiros;
- Retenções, bloqueios, glosas ou devoluções de valores transacionados entre o Contratante e seus Clientes Finais;
- Alterações unilaterais nas políticas, taxas ou condições operacionais da EfiBank ou de qualquer outro parceiro de pagamento;
- Atos de terceiros não contratados pelo Operador.

## 7. OBRIGAÇÕES DO CONTRATANTE

**7.1. CREDENCIAIS EFIBANK:**

**7.1.1.** O Contratante é o único e exclusivo responsável por:
- Obter, manter e gerenciar suas próprias credenciais de acesso à plataforma EfiBank (client_id, client_secret, chave PIX, certificado digital .p12 e senha do certificado);
- Manter as credenciais EfiBank sempre atualizadas, válidas e em conformidade com as exigências da EfiBank e do Banco Central do Brasil;
- Renovar o certificado digital .p12 periodicamente, conforme prazo de validade estabelecido pela EfiBank, sob pena de indisponibilidade do processamento de pagamentos PIX;
- Testar a conectividade com a EfiBank sempre que realizar alterações nas credenciais.

**7.1.2.** O Operador não é responsável por falhas no processamento de pagamentos PIX do Contratante decorrentes de credenciais desatualizadas, inválidas, expiradas ou incorretas.

**7.2. RESPONSABILIDADE EXCLUSIVA POR TRANSAÇÕES FINANCEIRAS:**

**7.2.1.** O Contratante reconhece e aceita que:
- Todas as transações financeiras realizadas entre o Contratante e seus Clientes Finais por meio da plataforma SaaS Mesa são processadas exclusivamente com as credenciais EfiBank do próprio Contratante;
- O Operador não é intermediário financeiro, instituição de pagamento, correspondente bancário ou prestador de serviço de pagamento, nos termos da regulamentação do Banco Central do Brasil;
- O Operador não detém, não processa, não retém e não tem acesso aos valores transacionados entre o Contratante e seus Clientes Finais;
- O Contratante é o único responsável pela liquidação, compensação, devolução, estorno, chargeback e quaisquer outras obrigações financeiras decorrentes das transações com seus Clientes Finais;
- O Contratante assume integral responsabilidade pela conformidade de suas operações com a legislação fiscal, tributária, trabalhista e securitária aplicável.

**7.3. USO ACEITÁVEL DA PLATAFORMA:**

**7.3.1.** O Contratante se obriga a utilizar a plataforma SaaS Mesa exclusivamente para os fins previstos neste contrato, abstendo-se de:
- Utilizar a plataforma para atividades ilícitas, fraudulentas ou que violem direitos de terceiros;
- Veicular conteúdo ofensivo, discriminatório, pornográfico ou que incite violência;
- Realizar testes de invasão, varreduras de vulnerabilidade ou quaisquer atividades de segurança ofensiva sem autorização prévia e expressa do Operador;
- Sobrecarregar intencionalmente a infraestrutura da plataforma;
- Utilizar a plataforma para armazenar ou transmitir malware, vírus ou qualquer código malicioso.

**7.4. VERACIDADE DOS DADOS CADASTRAIS:**

**7.4.1.** O Contratante declara, sob as penas da lei, que todas as informações fornecidas no momento do cadastro e durante a vigência do contrato são verdadeiras, precisas, completas e atualizadas, responsabilizando-se civil e criminalmente por informações falsas, inexatas ou desatualizadas.

**7.5. SEGURANÇA DAS CREDENCIAIS DE ACESSO:**

**7.5.1.** O Contratante é o único responsável pela guarda, sigilo e uso adequado de todas as credenciais de acesso à plataforma, incluindo, sem limitação:
- Senhas de usuários administradores, atendentes e clientes;
- Tokens de autenticação da API para entregadores (DeliveryPerson.api_token);
- Chaves de API e demais mecanismos de autenticação.

**7.5.2.** O Contratante deverá comunicar imediatamente o Operador em caso de comprometimento, suspeita de uso não autorizado ou vazamento de credenciais.

**7.5.3.** O Operador não se responsabiliza por acessos não autorizados, perda de dados ou prejuízos decorrentes do uso indevido, compartilhamento, extravio ou comprometimento das credenciais de acesso do Contratante.

**7.6. CONFORMIDADE LEGAL:**

**7.6.1.** O Contratante é o único responsável por:
- Emitir notas fiscais e cumprir todas as obrigações tributárias (federais, estaduais e municipais) decorrentes de suas atividades comerciais;
- Cumprir a legislação trabalhista e previdenciária em relação a seus funcionários, atendentes e entregadores;
- Obter as licenças e alvarás de funcionamento exigidos pela legislação municipal;
- Cumprir a legislação sanitária aplicável ao seu ramo de atividade;
- Manter a conformidade com a Lei Geral de Proteção de Dados (Lei 13.709/2018) em relação ao tratamento de dados de seus Clientes Finais;
- Adotar, publicar e manter disponível aos seus Clientes Finais os Termos de Uso e Política de Privacidade específicos para consumidores, em conformidade com a LGPD e o Código de Defesa do Consumidor, que deverão ser aceitos pelos Clientes Finais no momento do primeiro pedido ou cadastro no sistema. O Operador disponibiliza ao Contratante um modelo padrão de tais Termos, que poderá ser adaptado pelo Contratante conforme suas necessidades específicas.

**7.7. PROIBIÇÕES:**

**7.7.1.** É expressamente vedado ao Contratante:
- Revender, sublicenciar, ceder, transferir ou compartilhar o acesso à plataforma com terceiros não autorizados;
- Realizar engenharia reversa, descompilar, desmontar, modificar, traduzir ou criar obras derivadas do software SaaS Mesa ou de qualquer de seus componentes;
- Utilizar a plataforma para processar dados de terceiros que não sejam diretamente relacionados à sua atividade comercial;
- Utilizar robôs, spiders, scrapers ou qualquer mecanismo automatizado para acessar, extrair ou copiar dados da plataforma.

## 8. POLÍTICA DE DADOS E LGPD

**8.1. NATUREZA DOS DADOS COLETADOS:**

**8.1.1.** No âmbito da plataforma SaaS Mesa, são coletados e tratados os seguintes dados pessoais e informações:

**8.1.1.1. Dados do Contratante (Tenant):** nome empresarial, nome fantasia, e-mail corporativo, CNPJ ou CPF, número de WhatsApp, slug identificador, domínio personalizado, logotipo, horários de funcionamento, configurações de servidor SMTP (host, porta, usuário, senha, criptografia, endereço e nome de remetente), endereço e número de telefone.

**8.1.1.2. Dados de Usuários do Contratante:** nome, e-mail (único por Tenant), função/permissão (role), hash de senha, token de autenticação, dados de verificação de e-mail e credenciais de passkey.

**8.1.1.3. Dados dos Clientes Finais:** nome, telefone, endereço de entrega (armazenado em campo `address_json` no formato JSON), histórico de pedidos, preferências de consumo.

**8.1.1.4. Dados de Pedidos:** itens consumidos, valor total, método de pagamento, status, data/hora, cupom de desconto aplicado, forma de pagamento, valor de troco.

**8.1.1.5. Dados de Pagamento:** método de pagamento (PIX, cartão de crédito, cartão de débito, dinheiro), status do pagamento, identificador de transação EfiBank (charge_id/txid), data de pagamento. O Operador **não armazena** dados de cartão de crédito ou débito (número, bandeira, código de segurança, data de validade), limitando-se a registrar o método e o status do pagamento.

**8.1.1.6. Dados de Entregadores:** nome, telefone, status, token de API para acesso ao aplicativo mobile.

**8.1.1.7. Dados de Credenciais EfiBank:** *client_id*, *client_secret*, chave PIX, certificado digital (.p12) e senha do certificado, todos armazenados de forma criptografada (AES-256-GCM).

**8.2. BASES LEGAIS DE TRATAMENTO:**

**8.2.1.** O tratamento de dados pessoais realizado pelo Operador tem como fundamento as seguintes bases legais previstas na Lei 13.709/2018 (LGPD):
- **Execução do contrato** (art. 7º, V, e art. 11, II, "a"): para a prestação dos serviços contratados, incluindo a operação da plataforma, processamento de pedidos e integração com sistemas de pagamento;
- **Legítimo interesse** (art. 7º, IX, e art. 10): para a melhoria dos serviços, segurança da plataforma, prevenção a fraudes e comunicação com o Contratante;
- **Obrigação legal ou regulatória** (art. 7º, II, e art. 11, II, "b"): para cumprimento de obrigações fiscais, contábeis, regulatórias e ordens judiciais;
- **Exercício regular de direitos** (art. 7º, VI, e art. 11, II, "f"): em processos judiciais, administrativos ou arbitrais.

**8.3. COMPARTILHAMENTO DE DADOS:**

**8.3.1.** O Operador poderá compartilhar dados do Contratante e de seus Clientes Finais com:
- **EfiBank:** para processamento das transações PIX, sendo o compartilhamento restrito aos dados estritamente necessários para a operação de pagamento (valor, identificador da transação, chave PIX de destino);
- **Provedores de infraestrutura em nuvem:** para hospedagem e armazenamento dos dados;
- **Autoridades competentes:** mediante requisição judicial, administrativa ou policial, nos termos da lei.

**8.3.2.** O Operador não comercializa, aluga ou transfere dados pessoais a terceiros para fins de marketing direto.

**8.4. PRAZO DE RETENÇÃO DOS DADOS:**

**8.4.1.** Os dados pessoais serão mantidos pelo Operador durante toda a vigência do contrato e, após o seu término, pelo prazo mínimo de 30 (trinta) dias para permitir a exportação pelo Contratante (Cláusula 12), podendo ser retidos por prazos superiores para cumprimento de obrigação legal ou regulatória.

**8.5. DIREITOS DOS TITULARES:**

**8.5.1.** O Operador assegura aos titulares de dados pessoais os direitos previstos no art. 18 da LGPD, incluindo:
- Confirmação da existência de tratamento;
- Acesso aos dados;
- Correção de dados incompletos, inexatos ou desatualizados;
- Anonimização, bloqueio ou eliminação de dados desnecessários, excessivos ou tratados em desconformidade com a lei;
- Portabilidade dos dados a outro fornecedor de serviço ou produto;
- Eliminação dos dados tratados com base no consentimento;
- Informação sobre o compartilhamento de dados;
- Revogação do consentimento.

**8.5.2.** As solicitações relativas aos direitos dos titulares deverão ser encaminhadas ao Encarregado (DPO) do Operador.

**8.6. ENCARREGADO (DPO):**

**8.6.1.** O Operador designa como Encarregado pelo tratamento de dados pessoais (Data Protection Officer) o Sr(a). [NOME DO DPO], que pode ser contatado pelo e-mail [EMAIL DO DPO] para qualquer assunto relacionado ao tratamento de dados pessoais.

**8.7. TRANSFERÊNCIA INTERNACIONAL DE DADOS:**

**8.7.1.** O Operador não realiza transferência internacional de dados pessoais para países que não ofereçam grau adequado de proteção, exceto quando:
- Autorizado expressamente pelo titular;
- Necessário para execução do contrato;
- Exigido por obrigação legal;
- Realizado para países que ofereçam grau de proteção adequado reconhecido pela Autoridade Nacional de Proteção de Dados (ANPD).

**8.8. RESPONSABILIDADE DO CONTRATANTE COMO CONTROLADOR:**

**8.8.1.** O Contratante é o **Controlador** dos dados pessoais de seus Clientes Finais, nos termos da LGPD, sendo o Operador o **Operador** de tais dados.

**8.8.2.** O Contratante declara que:
- Dispõe de base legal adequada para coletar e compartilhar com o Operador os dados de seus Clientes Finais;
- Informará seus Clientes Finais sobre o compartilhamento de dados com o Operador;
- Responderá perante seus Clientes Finais e perante as autoridades competentes pelo cumprimento da LGPD em relação aos dados de que é Controlador.

## 9. SEGURANÇA DA INFORMAÇÃO

**9.1. MEDIDAS TÉCNICAS E ORGANIZACIONAIS:**

**9.1.1.** O Operador adota as seguintes medidas de segurança, técnicas e administrativas, para proteger os dados tratados contra acessos não autorizados, destruição, perda, alteração, comunicação ou qualquer forma de tratamento inadequado ou ilícito:

**9.1.1.1. Criptografia em Repouso:** As credenciais EfiBank do Contratante (client_id, client_secret, pix_key, certificado .p12, senha do certificado) são armazenadas no banco de dados utilizando criptografia AES-256-GCM, com chave de criptografia dedicada configurada na variável de ambiente `TENANT_CREDENTIAL_ENCRYPTION_KEY`, derivada via HKDF-SHA256 com salt específico. As senhas dos usuários são protegidas mediante hash bcrypt.

**9.1.1.2. Criptografia em Trânsito:** Todas as comunicações entre o cliente (navegador web ou aplicativo mobile) e os servidores da plataforma são protegidas por TLS/SSL.

**9.1.1.3. Controle de Acesso:** O sistema implementa autenticação multifator (suporte a passkeys), papéis de acesso granulares (superadmin, admin, atendente, cliente) e políticas de sessão seguras.

**9.1.1.4. Rate Limiting (Throttle):** O sistema implementa limitação de taxa de requisições de autenticação para prevenção de ataques de força bruta: 10 (dez) requisições por minuto na rota de login web e 5 (cinco) requisições por minuto na rota de login da API. As rotas de recuperação de senha também possuem limitação de 60 (sessenta) segundos entre requisições.

**9.1.1.5. Validação de Webhooks:** As notificações recebidas da EfiBank são validadas mediante assinatura HMAC-SHA256 (cabeçalho `x-efi-hmac-sha256`) e verificação de IP de origem contra lista de IPs autorizados (produção: 54.94.56.243, 54.94.43.18, 54.232.206.88; sandbox: 177.71.168.182, 54.94.56.243).

**9.1.1.6. Headers de Segurança:** A plataforma implementa cabeçalhos de segurança HTTP, incluindo X-Frame-Options, X-Content-Type-Options, Content-Security-Policy (permitindo conexões com `*.saasmesa.com.br` e `*.efipay.com.br`) e HSTS (Strict-Transport-Security) em ambiente de produção.

**9.1.1.7. Logs de Auditoria:** As requisições de webhook são registradas na tabela `webhook_logs` com payload completo, assinatura, resultado da validação e status de processamento.

**9.2. RESPONSABILIDADE DO CONTRATANTE:**

**9.2.1.** O Contratante é responsável por:
- Manter sigilo absoluto de suas credenciais de acesso (senhas, tokens, chaves de API);
- Utilizar senhas fortes e não reutilizadas em outros serviços;
- Não compartilhar credenciais de acesso com pessoas não autorizadas;
- Comunicar imediatamente ao Operador qualquer comprometimento ou suspeita de comprometimento de credenciais;
- Revogar o acesso de usuários que não mais necessitem dele (funcionários desligados, atendentes desautorizados);
- Manter atualizadas as informações cadastrais.

**9.3. NOTIFICAÇÃO DE INCIDENTE DE SEGURANÇA:**

**9.3.1.** Em caso de incidente de segurança que possa acarretar risco ou dano relevante aos titulares de dados pessoais, o Operador notificará:
- Os titulares afetados, descrevendo a natureza do incidente, as medidas de mitigação adotadas e as recomendações de segurança;
- A Autoridade Nacional de Proteção de Dados (ANPD), no prazo de 72 (setenta e duas) horas, conforme art. 48 da LGPD.

**9.3.2.** O Contratante compromete-se a notificar o Operador imediatamente (e em nenhuma hipótese em prazo superior a 24 horas) ao tomar conhecimento de qualquer incidente de segurança envolvendo dados armazenados ou processados na plataforma.

## 10. PROPRIEDADE INTELECTUAL

**10.1.** O software SaaS Mesa, incluindo, mas não se limitando a, seu código-fonte, código-objeto, estrutura, arquitetura (incluindo a arquitetura multi-tenant), design, interface gráfica, banco de dados, algoritmos, bibliotecas, frameworks (Laravel, PHP, Livewire, Alpine.js, Tailwind CSS, Vite, endroid/qr-code), documentação técnica, manuais, nomes comerciais, marcas, logotipos, domínios e demais ativos de propriedade intelectual, são de propriedade exclusiva do Operador, protegidos pela Lei de Direitos Autorais (Lei 9.610/98), pela Lei de Propriedade Industrial (Lei 9.279/96), pela Lei de Software (Lei 9.609/98) e pelos tratados internacionais dos quais o Brasil seja signatário.

**10.2.** Este contrato concede ao Contratante uma licença de uso limitada, não exclusiva, não transferível, não sublicenciável, pessoal e intransferível, pelo prazo de vigência do contrato, para acessar e utilizar a plataforma SaaS Mesa exclusivamente para a gestão de seu estabelecimento comercial.

**10.3.** O Contratante **não adquire**, por força deste contrato, qualquer direito de propriedade intelectual sobre o SaaS Mesa ou qualquer de seus componentes, incluindo:
- Direitos sobre o código-fonte ou código-objeto;
- Direitos de reprodução, distribuição, comercialização ou modificação;
- Direitos de criar obras derivadas;
- Direitos sobre marcas, nomes comerciais ou domínios do Operador.

**10.4.** O Contratante declara que é o único responsável pelo conteúdo que inserir na plataforma, incluindo, sem limitação:
- Logotipos, marcas e identidade visual do estabelecimento;
- Fotografias, descrições e preços de produtos e itens do cardápio;
- Informações nutricionais e de ingredientes;
- Textos, imagens, vídeos ou quaisquer outros materiais publicados.

**10.5.** O Contratante garante que detém todos os direitos necessários sobre o conteúdo inserido na plataforma e que tal conteúdo não viola direitos de propriedade intelectual de terceiros, nem leis de concorrência, publicidade ou proteção ao consumidor.

## 11. LIMITAÇÃO DE RESPONSABILIDADE

**11.1.** O Operador não será responsável por:

**11.1.1. Falhas de Terceiros:** Por quaisquer danos, perdas, prejuízos, atrasos ou indisponibilidades decorrentes de:
- Falhas, erros, instabilidades ou indisponibilidade da EfiBank, do Sistema de Pagamentos Instantâneos (SPI) do Banco Central do Brasil, ou de quaisquer instituições de pagamento, operadoras de cartão ou bancos;
- Retenções, bloqueios, glosas, chargebacks, devoluções ou estornos de valores transacionados entre o Contratante e seus Clientes Finais;
- Ações ou omissões de provedores de infraestrutura de nuvem, internet, energia elétrica ou telecomunicações.

**11.1.2. Uso Indevido:** Por quaisquer perdas ou danos decorrentes de:
- Uso indevido da plataforma pelo Contratante, seus usuários, funcionários, atendentes, entregadores ou Clientes Finais;
- Comprometimento de credenciais de acesso por negligência do Contratante;
- Descumprimento das obrigações legais, fiscais, trabalhistas ou regulatórias a cargo do Contratante;
- Conteúdo inserido pelo Contratante na plataforma que viole direitos de terceiros ou disposições legais.

**11.1.3. Danos Indiretos:** Por lucros cessantes, perda de receita, perda de oportunidade, danos à reputação, interrupção de negócios ou quaisquer danos indiretos, incidentais, punitivos ou consequenciais, ainda que tenha sido informada da possibilidade de tais danos.

**11.2. TETO DE INDENIZAÇÃO:**

**11.2.1.** Em nenhuma hipótese a responsabilidade do Operador por danos diretos decorrentes deste contrato ou da utilização da plataforma excederá o valor total efetivamente pago pelo Contratante ao Operador nos 3 (três) meses imediatamente anteriores ao evento que deu origem à reivindicação.

**11.2.2.** Para Contratantes do Plano Gratuito, o teto de indenização será limitado ao valor de R$ 100,00 (cem reais).

**11.3. EXCLUSÃO DE GARANTIAS:**

**11.3.1.** A plataforma é fornecida "no estado em que se encontra" ("as is"), dentro dos melhores esforços do Operador, não sendo o Operador obrigado a garantir que a plataforma atenda a necessidades específicas do Contratante, seja isenta de erros, opere ininterruptamente ou seja compatível com qualquer hardware, software ou sistema de terceiros.

**11.3.2.** O Operador não garante a precisão, completeza ou atualidade das informações financeiras, relatórios ou extratos gerados pela plataforma, que devem ser utilizados como ferramenta de apoio gerencial, não substituindo controles contábeis e financeiros independentes.

## 12. CANCELAMENTO E PORTABILIDADE DE DADOS

**12.1. CANCELAMENTO PELO CONTRATANTE:**

**12.1.1.** O Contratante poderá cancelar seu plano e encerrar o uso da plataforma a qualquer momento, mediante solicitação expressa enviada ao Operador pelo e-mail de contato ou através da funcionalidade de cancelamento no painel administrativo.

**12.1.2.** O cancelamento do plano não desobriga o Contratante do pagamento de valores eventualmente devidos até a data do cancelamento.

**12.1.3.** Em caso de cancelamento durante o período de trial, nenhum valor será devido.

**12.2. EXPORTAÇÃO DE DADOS:**

**12.2.1.** O Contratante poderá solicitar a exportação de seus dados no prazo de até 30 (trinta) dias contados da data do cancelamento ou do término do contrato.

**12.2.2.** A exportação será realizada pelo Operador no prazo de até 15 (quinze) dias úteis contados da solicitação, em formato estruturado e de uso corrente (CSV, JSON ou outro formato a ser definido pelo Operador), abrangendo os dados cadastrais do Tenant, dados de pedidos, dados de clientes e demais informações inseridas pelo Contratante na plataforma.

**12.2.3.** Expirado o prazo de 30 (trinta) dias sem solicitação de exportação, o Operador poderá proceder à exclusão definitiva dos dados, observado o disposto na Cláusula 12.3.

**12.3. EXCLUSÃO DE DADOS:**

**12.3.1.** Após o encerramento do contrato, os dados do Contratante não são excluídos imediatamente do banco de dados, sendo aplicado o mecanismo de SoftDelete (exclusão lógica), que mantém os dados no banco de dados com uma marcação de exclusão, permitindo eventual recuperação em caso de reativação ou solicitação tempestiva do Contratante.

**12.3.2.** Transcorrido o prazo de 30 (trinta) dias do encerramento sem manifestação do Contratante, os dados poderão ser definitivamente excluídos (hard delete) pelo Operador, exceto aqueles cuja retenção seja exigida por obrigação legal ou regulatória.

**12.3.3.** O Operador reserva-se o direito de reter, mesmo após a exclusão, logs técnicos de operação, registros de acesso, registros de webhook e metadados anonimizados necessários para segurança da plataforma e cumprimento de obrigações legais.

## 13. VIGÊNCIA E RESCISÃO

**13.1. VIGÊNCIA:**

**13.1.1.** O presente contrato é celebrado por prazo indeterminado, entrando em vigor na data do cadastro eletrônico do Contratante na plataforma e permanecendo válido até sua rescisão por qualquer das Partes, nos termos desta cláusula.

**13.1.2.** Para contratos de Plano Premium com pagamento antecipado de múltiplos meses (3, 6 ou 12 meses), o período contratado mínimo será equivalente ao período pago, renovando-se automaticamente por períodos mensais sucessivos após o término do período contratado, salvo manifestação em contrário do Contratante.

**13.2. RESCISÃO POR JUSTA CAUSA:**

**13.2.1.** Qualquer das Partes poderá rescindir o presente contrato, independentemente de notificação judicial ou extrajudicial, nas seguintes hipóteses:

**13.2.1.1. Pelo Operador:**
- Inadimplemento de qualquer obrigação pecuniária pelo Contratante por prazo superior a 5 (cinco) dias corridos;
- Violação grave de qualquer disposição deste contrato;
- Utilização fraudulenta ou ilícita da plataforma;
- Comprometimento da segurança da plataforma por ação ou omissão do Contratante;
- Prática de atos que causem dano à reputação do Operador ou da plataforma;
- Indícios de lavagem de dinheiro, financiamento ao terrorismo ou corrupção;
- Descumprimento de obrigações legais, fiscais ou regulatórias pelo Contratante;
- Tentativa de burlar os mecanismos de segurança, isolamento multi-tenant ou controle de acesso da plataforma.

**13.2.1.2. Pelo Contratante:**
- Descumprimento pelo Operador de obrigação essencial do contrato, não sanado no prazo de 30 (trinta) dias após notificação;
- Violação da LGPD pelo Operador que cause dano ao Contratante.

**13.3. RESCISÃO SEM JUSTA CAUSA:**

**13.3.1.** Qualquer das Partes poderá rescindir o presente contrato sem justa causa, mediante notificação por escrito à outra Parte com aviso prévio mínimo de 30 (trinta) dias.

**13.3.2.** Na hipótese de rescisão sem justa causa pelo Contratante que tenha contratado plano com pagamento antecipado de múltiplos meses, não haverá devolução proporcional dos valores já pagos, salvo disposição legal em contrário.

**13.4. CONSEQUÊNCIAS DA RESCISÃO:**

**13.4.1.** A rescisão do contrato, por qualquer motivo, importará na:
- Suspensão imediata e definitiva do acesso do Contratante a todas as funcionalidades da plataforma;
- Inabilitação de todos os usuários vinculados ao Tenant (administradores, atendentes, clientes, entregadores);
- Indisponibilidade dos endpoints da API e do aplicativo mobile dos entregadores.

**13.4.2.** A rescisão não prejudica a exigibilidade de valores eventualmente devidos pelo Contratante até a data da rescisão.

## 14. FORO E LEI APLICÁVEL

**14.1. LEI APLICÁVEL:**

**14.1.1.** O presente contrato é regido pelas leis da República Federativa do Brasil, em especial pelo Código Civil (Lei 10.406/2002), pela Lei Geral de Proteção de Dados (Lei 13.709/2018), pelo Marco Civil da Internet (Lei 12.965/2014), pelo Código de Defesa do Consumidor (Lei 8.078/1990 — quando aplicável), pela Lei de Software (Lei 9.609/98), pela Lei de Direitos Autorais (Lei 9.610/98) e pelas normas do Banco Central do Brasil aplicáveis aos arranjos de pagamento.

**14.2. FORO:**

**14.2.1.** As Partes elegem o foro da comarca de [FORO] para dirimir quaisquer controvérsias decorrentes deste contrato, com renúncia expressa a qualquer outro, por mais privilegiado que seja.

**14.2.2.** Exceção: se o Contratante for pessoa física e o contrato for caracterizado como relação de consumo nos termos do Código de Defesa do Consumidor, o foro competente será o do domicílio do Contratante-consumidor.

**14.3. SOLUÇÃO ALTERNATIVA DE CONFLITOS:**

**14.3.1.** As Partes poderão, de comum acordo, submeter suas controvérsias à mediação ou arbitragem antes de recorrer ao Poder Judiciário, sem que isso constitua condição de procedibilidade.

## 15. DISPOSIÇÕES GERAIS

**15.1. ALTERAÇÕES DOS TERMOS:**

**15.1.1.** O Operador poderá alterar unilateralmente os presentes Termos de Uso e Condições Contratuais a qualquer tempo, mediante comunicação ao Contratante com antecedência mínima de 15 (quinze) dias, enviada para o e-mail cadastrado na plataforma.

**15.1.2.** O Contratante que não concordar com as alterações poderá rescindir o contrato sem penalidade, no prazo de 15 (quinze) dias contados do recebimento da comunicação, mediante solicitação expressa de cancelamento.

**15.1.3.** A continuidade do uso da plataforma após a entrada em vigor das alterações importará na automática aceitação dos novos termos pelo Contratante.

**15.2. INTEGRALIDADE DO ACORDO:**

**15.2.1.** O presente contrato constitui o integral entendimento entre as Partes, substituindo todas as comunicações, propostas, entendimentos e acordos anteriores, escritos ou verbais, relativos ao seu objeto.

**15.3. NULIDADE PARCIAL:**

**15.3.1.** Se qualquer cláusula, condição ou disposição deste contrato for considerada inválida, ilegal ou inexigível por autoridade competente, tal invalidade não afetará as demais disposições, que permanecerão em pleno vigor e efeito, devendo a cláusula inválida ser substituída por outra que, dentro dos limites legais, atenda ao propósito econômico e jurídico originalmente pretendido.

**15.4. COMUNICAÇÕES OFICIAIS:**

**15.4.1.** Todas as comunicações oficiais entre as Partes serão realizadas por escrito e enviadas para os endereços de e-mail cadastrados pelo Contratante na plataforma e para o e-mail oficial do Operador.

**15.4.2.** As comunicações enviadas para o e-mail cadastrado do Contratante consideram-se recebidas na data do envio, independentemente de confirmação de leitura, sendo de responsabilidade do Contratante manter seu endereço de e-mail sempre atualizado na plataforma.

**15.5. TOLERÂNCIA:**

**15.5.1.** A tolerância de qualquer das Partes quanto ao descumprimento de qualquer disposição contratual não constituirá renúncia a direitos nem precedente para novos descumprimentos, podendo a Parte exercer seus direitos a qualquer tempo.

**15.6. CESSÃO DO CONTRATO:**

**15.6.1.** O Contratante não poderá ceder, transferir ou delegar seus direitos ou obrigações decorrentes deste contrato, no todo ou em parte, sem o prévio e expresso consentimento por escrito do Operador.

**15.6.2.** O Operador poderá ceder este contrato a terceiros, no todo ou em parte, mediante comunicação ao Contratante, hipótese em que o cessionário assumirá integralmente as obrigações do Operador.

**15.7. INDEPENDÊNCIA DAS PARTES:**

**15.7.1.** Este contrato não cria qualquer vínculo societário, trabalhista, previdenciário, de parceria, joint venture, franquia, representação comercial, agência ou consórcio entre as Partes, que permanecem independentes e autônomas.

**15.7.2.** O Operador não é empregador, tomador de serviços ou contratante dos funcionários, atendentes, entregadores ou prestadores de serviço do Contratante.

**15.8. SOBREVIVÊNCIA:**

**15.8.1.** As disposições das Cláusulas 8 (Política de Dados e LGPD), 10 (Propriedade Intelectual), 11 (Limitação de Responsabilidade), 12 (Cancelamento e Portabilidade de Dados), 14 (Foro e Lei Aplicável) e 15 (Disposições Gerais) sobreviverão ao término ou rescisão deste contrato.

---

**Data de vigência:** [DATA]

**Operador:** [RAZÃO SOCIAL DO OPERADOR]
**CNPJ/CPF:** [CNPJ]
**E-mail para comunicações:** [E-MAIL DO OPERADOR]
**Encarregado (DPO):** [NOME DO DPO] — [EMAIL DO DPO]
**Termos para Clientes Finais:** O Contratante obriga-se a adotar, publicar e manter disponíveis aos seus Clientes Finais os Termos de Uso e Política de Privacidade específicos para consumidores (Documento 2 da plataforma), nos termos da Cláusula 7.6.1, responsabilizando-se integralmente pelo seu conteúdo e observância perante seus consumidores e autoridades competentes.

**Contratante:** O estabelecimento identificado no cadastro eletrônico da plataforma SaaS Mesa, que declara ter lido, compreendido e aceitado todos os termos e condições deste instrumento, vinculando-se desde já ao seu inteiro teor.
