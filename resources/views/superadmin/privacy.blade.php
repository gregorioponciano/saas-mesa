@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6 max-w-5xl">
    <div>
        <h1 class="text-2xl font-bold text-white">Privacidade e LGPD</h1>
        <p class="mt-1 text-sm text-neutral-400">Política de retenção, direitos dos titulares e ferramentas de conformidade</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
            <h3 class="font-bold text-white">Retenção de backups</h3>
            <div class="mt-3 space-y-2 text-sm text-neutral-400">
                <p><span class="text-neutral-200 font-medium">Plano Gratuito:</span> backups retidos por até 7 dias, máximo de 3 backups por empresa.</p>
                <p><span class="text-neutral-200 font-medium">Plano Premium:</span> retenção ilimitada, máximo de 30 backups por empresa.</p>
                <p><span class="text-neutral-200 font-medium">Limpeza automática:</span> agendada diariamente às 03:00 (comando <code class="text-amber-400 font-mono">backups:purge</code>).</p>
            </div>
        </div>

        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
            <h3 class="font-bold text-white">Direitos dos titulares (art. 18, LGPD)</h3>
            <div class="mt-3 space-y-2 text-sm text-neutral-400">
                <p><span class="text-neutral-200 font-medium">Acesso/Portabilidade:</span> exportação JSON de todos os dados da empresa — disponível no painel da empresa e pelo superadmin (Empresas → Exportar dados).</p>
                <p><span class="text-neutral-200 font-medium">Eliminação/Anonimização:</span> anonimiza usuários, entregadores e dados cadastrais da empresa, cancelando a assinatura e removendo backups (Empresas → Anonimizar e encerrar).</p>
                <p><span class="text-neutral-200 font-medium">Oposição ao uso:</span> suspensão imediata da empresa via botão de suspensão.</p>
            </div>
        </div>

        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
            <h3 class="font-bold text-white">O que a anonimização apaga</h3>
            <div class="mt-3 space-y-2 text-sm text-neutral-400">
                <p>• Nomes e e-mails de todos os usuários e entregadores da empresa</p>
                <p>• Senhas e tokens de acesso (irreversível — o acesso à conta é perdido)</p>
                <p>• Endereço, telefone, CPF, CNH e placa de veículos</p>
                <p>• Logos e credenciais de pagamento (EFI)</p>
                <p>• Backups armazenados da empresa (arquivo + registro)</p>
                <p class="pt-2 text-xs text-neutral-500">Os pedidos e registros operacionais são mantidos anonimizados para fins contábeis e antifraude (art. 7º, inc. V e VI, LGPD), sem qualquer dado pessoal identificável.</p>
            </div>
        </div>

        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
            <h3 class="font-bold text-white">Registro de atividades</h3>
            <div class="mt-3 space-y-2 text-sm text-neutral-400">
                <p>• Toda ação administrativa do superadmin é registrada em <span class="text-neutral-200 font-medium">Auditoria</span> com data, IP e operador.</p>
                <p>• Exportações LGPD e anonimizações ficam documentadas com dados mascarados (e-mails exibidos parcialmente).</p>
                <p>• Logs de webhooks de pagamento ficam disponíveis em <span class="text-neutral-200 font-medium">Webhooks</span>.</p>
                <p class="pt-2">Políticas públicas: <a href="{{ route('privacy') }}" class="text-amber-400 hover:text-amber-300 font-medium">Política de Privacidade</a> · <a href="{{ route('terms') }}" class="text-amber-400 hover:text-amber-300 font-medium">Termos de Uso</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
