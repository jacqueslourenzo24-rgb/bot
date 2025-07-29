<?php

// Inclui o autoloader do Composer para carregar as classes da biblioteca
require __DIR__ . '/vendor/autoload.php';

// Usa as classes necessárias da biblioteca do Telegram
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Update;

// --- Configurações do Bot ---
// SEU TOKEN DO BOT: O token que você obteve do @BotFather.
const TELEGRAM_BOT_TOKEN = '8453831783:AAFtsSVLCCh4bmxl4MhTG9yFOsk1_i23cag';

// Caminho para o arquivo de LOG de cliques.
// ATENÇÃO: EM PRODUÇÃO REAL, VOCÊ DEVE USAR UM BANCO DE DADOS (MySQL, PostgreSQL, SQLite) AQUI
// para garantir persistência, relatórios e segurança dos dados.
const CLICKS_LOG_FILE = __DIR__ . '/clicks.log';

// --- Inicialização da API do Telegram ---
$telegram = new Api(TELEGRAM_BOT_TOKEN);

try {
    // 1. Recebe a atualização (Update) enviada pelo Telegram via Webhook
    $update = $telegram->getWebhookUpdate();

    // --- 2. Lógica para processar comandos (enviar links rastreáveis) ---
    // Verifica se a atualização contém uma mensagem de texto e se é um comando
    if ($update->getMessage() && $update->getMessage()->getText()) {
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();
        $command = $message->getText();

        // Se o comando for '/enviar_link', o bot envia a mensagem com o botão rastreável
        if ($command === '/enviar_link') {
            $linkUrl = "https://www.google.com/search?q=rastreamento+de+links+telegram"; // Seu link original para ser rastreado
            $linkId = "meu_link_rastreavel_abc"; // UM ID ÚNICO PARA ESTE LINK ESPECÍFICO (para rastreamento)

            // Cria o botão inline com o URL de destino e os dados de callback para rastreamento
            $keyboard = Keyboard::make()->inline()
                ->row([
                    Keyboard::inlineButton(['text' => 'Clique para Acessar o Link!', 'url' => $linkUrl, 'callback_data' => "click_{$linkId}"])
                ]);

            // Envia a mensagem com o botão para o chat de onde veio o comando
            $telegram->sendMessage([
                'chat_id'      => $chatId,
                'text'         => '🔗 **Novo Link Rastreado!** Clique no botão abaixo para acessar:',
                'reply_markup' => $keyboard,
                'parse_mode'   => 'Markdown' // Permite formatação como negrito
            ]);
            error_log("Comando /enviar_link executado. Link '{$linkUrl}' com ID '{$linkId}' enviado para o chat {$chatId}.");
        }
    }

    // --- 3. Lógica para rastrear cliques em botões inline (callback_query) ---
    // Esta parte do código será executada quando um usuário clicar em um botão inline que seu bot enviou.
    if ($update->getCallbackQuery()) {
        $callbackQuery = $update->getCallbackQuery();
        $callbackData = $callbackQuery->getData(); // Os dados que definimos no botão (ex: 'click_meu_link_rastreavel_abc')
        $queryId = $callbackQuery->getId(); // ID único da callback query, necessário para responder ao Telegram

        // Verifica se o 'callbackData' começa com 'click_' para identificar nossos cliques rastreáveis
        if (str_starts_with($callbackData, 'click_')) {
            $linkId = str_replace('click_', '', $callbackData); // Extrai o ID do link (ex: 'meu_link_rastreavel_abc')

            // --- REGISTRO DO CLIQUE ---
            // IMPORTANTE: Este é um LOG SIMPLIFICADO em arquivo.
            // EM UM AMBIENTE DE PRODUÇÃO REAL, VOCÊ DEVE SALVAR ESTES DADOS EM UM BANCO DE DADOS
            // para garantir persistência, organização e facilitar a geração de relatórios.
            $userId = $callbackQuery->getFrom()->getId();
            $username = $callbackQuery->getFrom()->getUsername() ?? 'N/A';
            $firstName = $callbackQuery->getFrom()->getFirstName();

            $logEntry = sprintf(
                "[%s] Link ID: %s - Usuário: %s (Nome: %s, @%s)\n",
                date('Y-m-d H:i:s'),
                $linkId,
                $userId,
                $firstName,
                $username
            );
            file_put_contents(CLICKS_LOG_FILE, $logEntry, FILE_APPEND);
            error_log("Clique rastreado: " . $logEntry);

            // --- Responde ao Callback Query ---
            // É crucial chamar answerCallbackQuery() para que o Telegram saiba que a query foi processada.
            // Isso evita que o Telegram continue enviando a mesma query e oferece feedback ao usuário.
            $telegram->answerCallbackQuery([
                'callback_query_id' => $queryId,
                'text'              => 'Obrigado por clicar!', // Mensagem pop-up discreta para o usuário
                'show_alert'        => false, // 'false' para pop-up discreto; 'true' para um alerta maior
                'cache_time'        => 0 // Não cachear a resposta
            ]);
        }
    }

} catch (Exception $e) {
    // 4. Tratamento de Erros: Registra quaisquer exceções que ocorram no processamento
    // Em um ambiente de produção real, use um sistema de logging mais robusto (ex: Monolog).
    error_log("Erro Crítico no Webhook: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
}

// 5. Resposta HTTP para o Telegram:
// O Telegram espera uma resposta HTTP 200 OK para saber que a atualização foi recebida com sucesso.
// Nenhuma saída de HTML ou texto é necessária aqui, apenas o status 200.
http_response_code(200);

?>