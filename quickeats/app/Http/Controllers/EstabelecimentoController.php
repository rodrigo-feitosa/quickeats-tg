<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estabelecimento;
use App\Models\ConfirmacaoEmail;
use App\Models\MensagensEstab;
use App\Models\GradeHorario;
use App\Mail\ConfirmaEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB; // Para interagir com o banco de dados
use App\Http\Controllers\Controller; // Para estender a classe base do Laravel
use App\Rules\validaCelular;
use App\Rules\validaCNPJ;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetSenhaEmail;
use App\Models\ResetSenha;
use App\Models\LogsToken;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Refund;

class EstabelecimentoController extends Controller
{
    public function cadastrarEstabelecimento(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nomeFantasiaSignup' => 'required|string|max:55',
                'cnpjSignup' => ['required', new validaCNPJ, 'unique:estabelecimentos,cnpj'],
                'telefoneSignup' => ['required', new validaCelular],
                'logradouroSignup' => 'required|string|max:100',
                'numeroSignup' => 'required|string|regex:/^[a-zA-Z0-9]+$/',
                'bairroSignup' => 'required|string|max:100',
                'cidadeSignup' => 'required|string|max:100',
                'estadoSignup' => 'required|string|max:2',
                'cepSignup' => 'required|string|min:9|max:9',
                'emailSignup' => 'required|string|email|max:255|unique:estabelecimentos,email',
                'senhaSignup' => 'required|string|min:8',
            ], [
                'cnpjSignup.unique' => 'Este CNPJ já está cadastrado.',
                'emailSignup.unique' => 'Este e-mail já está cadastrado.',
                'cepSignup.min' => 'CEP deve ter no mínimo 8 caracteres',
                'senhaSignup.min' => 'A senha deve ter no mínimo 8 caracteres',
                'numeroSignup.regex' => 'O número deve conter apenas letras e números, sem caracteres especiais ou números negativos.',
            ]);

            $estabelecimento = Estabelecimento::cadastrarEstabelecimento($validatedData);

            if (!$estabelecimento) {
                return redirect()->back()->with('error', 'Erro ao cadastrar estabelecimento. Tente novamente.');
            }

            $token = Str::random(60);

            // Inserir o token no banco de dados
            $confirmacao = ConfirmacaoEmail::create([
                'email' => $estabelecimento->email,
                'token' => $token,
                'criado_em' => now(),
                'id_usuario' => $estabelecimento->id_estab,
                'tipo_usuario' => 'estabelecimento',
            ]);

            if (!$confirmacao) {
                return redirect()->back()->with('error', 'Erro ao cadastrar estabelecimento. Tente novamente.');
            }

            try {
                Mail::to($estabelecimento->email)->send(new ConfirmaEmail($token, $estabelecimento->email, 'estabelecimento'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Erro ao enviar e-mail de confirmação.');
            }

            return redirect()->route('index_restaurante')->with('success', 'Estabelecimento cadastrado com sucesso!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ocorreu um erro inesperado. Tente novamente.');
        }
    }

    public function realizarLogin(Request $request)
    {
        // Validação dos campos de entrada
        $validatedData = $request->validate([
            'emailLogin' => 'required|string|email|max:255',
            'senhaLogin' => 'required|string|min:8',
        ]);

        $emailVerificado = Estabelecimento::where('email', $validatedData['emailLogin'])->where('email_verificado', 1)->first();
        $perfilAtivo = Estabelecimento::where('email', $validatedData['emailLogin'])->where('perfil_ativo', 1)->first();

        // Tentar autenticar o cliente usando o guard 'cliente'
        if (Auth::guard('estabelecimento')->attempt(['email' => $request->input('emailLogin'), 'password' => $request->input('senhaLogin')])) {
            if($perfilAtivo) {
                if($emailVerificado){
                    // Login bem-sucedido, redirecionar para a página inicial do profissional
                    return redirect()->route('home_restaurante')->with('success', 'Login realizado com sucesso!');
                } else {
                    return redirect()->back()->with('error', 'Email não verificado!');
                }
            } else {
                return redirect()->back()->with('error', 'Seu perfil está desativado');
            }
        } else {
            // Login falhou, redirecionar de volta com uma mensagem de erro
            return redirect()->back()->with('error', 'Email ou senha inválidos');
        }
    }

    public function exibirPaginaInicial()
    {
        // Obtendo o estabelecimento autenticado
        $estabelecimento = Auth::guard('estabelecimento')->user();

        // Pegando o ID do estabelecimento logado
        $idEstabelecimento = $estabelecimento->id_estab;

        // Executando o procedure e obtendo os pedidos
        $pedidos = DB::select("CALL exibir_pedidos_estabelecimento(?)", [$idEstabelecimento]);

        // Contadores para os diferentes status
        $totalPedidos = count($pedidos);
        $pendentes = collect($pedidos)->where('status_entrega', 2)->count();
        $preparacao = collect($pedidos)->where('status_entrega', 3)->count();
        $emRota = collect($pedidos)->where('status_entrega', 4)->count();
        $finalizados = collect($pedidos)->where('status_entrega', 5)->count();
        $avaliacao = DB::select('CALL calcular_media_avaliacoes(?)', [$idEstabelecimento]);

        $estoqueBaixo = DB::table('produtos')
                            ->where('id_estab', $idEstabelecimento)
                            ->where('qtd_estoque', '<', 10)
                            ->count();

        $exibirModal = empty($estabelecimento->razao_social)
                        || empty($estabelecimento->cpf_titular)
                        || empty($estabelecimento->rg_titular)
                        || empty($estabelecimento->cnae);

        return view('home_restaurante', compact('totalPedidos', 'pendentes', 'preparacao', 'emRota', 'finalizados', 'estoqueBaixo', 'avaliacao', 'exibirModal'));
    }

    public function salvarDadosComplementares(Request $request)
    {
        $request->validate([
            'razao_social' => 'required|string|max:255',
            'cpf_titular' => 'required|string|max:14',
            'rg_titular' => 'required|string|max:20',
            'cnae' => 'required|string|max:9',
        ]);

        $estabelecimento = Auth::guard('estabelecimento')->user();
        $estabelecimento->razao_social = $request->razao_social;
        $estabelecimento->cpf_titular = $request->cpf_titular;
        $estabelecimento->rg_titular = $request->rg_titular;
        $estabelecimento->cnae = $request->cnae;
        $estabelecimento->save();

        return redirect()->back()->with('success', 'Dados complementares salvos com sucesso!');
    }


    public function exibirPaginaPedidos()
    {
        // Obtendo o estabelecimento autenticado
        $idEstabelecimento = Auth::guard('estabelecimento')->id();

        // Executando o procedure e obtendo os pedidos
        $pedidos = DB::select("CALL exibir_pedidos_estabelecimento(?)", [$idEstabelecimento]);

        // Verificar quais pedidos já foram avaliados e obter a nota
        foreach ($pedidos as $pedido) {
            $avaliacao = DB::table('avaliacoes')
                ->where('id_pedido', $pedido->id_pedido)
                ->select('nota')
                ->first(); // Retorna a primeira correspondência

            // Adiciona os dados de avaliação ao pedido
            $pedido->avaliado = !is_null($avaliacao);
            $pedido->nota = $avaliacao->nota ?? null;
        }

        return view('pedidos_restaurante', compact('pedidos'));
    }

    public function alterarStatus(Request $request, $id)
    {
        // Verificar se o pedido existe
        $pedido = DB::select("SELECT * FROM pedidos WHERE id_pedido = ?", [$id]);

        if (empty($pedido)) {
            return redirect()->back()->with('error', 'Pedido não encontrado.');
        }

        $pedido = $pedido[0]; // Pega o primeiro resultado do array

        try {
            // Se o novo status for "7" ou 8, realizar o reembolso
            if ($request->novo_status == "7" || $request->novo_status == "8") {
                // Configurar a chave da Stripe
                Stripe::setApiKey(env('STRIPE_SECRET'));

                // Realizar o reembolso
                $reembolso = Refund::create([
                    'payment_intent' => $pedido->payment_intent_id, // Supondo que tenha esse campo na tabela pedidos
                    // 'amount' => opcional, se quiser reembolsar parcial
                ]);
            }

            // Atualizar o status do pedido no banco
            DB::update("UPDATE pedidos SET status_entrega = ? WHERE id_pedido = ?", [$request->novo_status, $id]);

            return redirect()->back()->with('success', 'Status atualizado com sucesso.' .
                ($request->novo_status == "6" ? ' Reembolso realizado.' : '')
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    // Método para ir para a página de adm
    public function exibirAdmRestaurante()
    {
        return view('adm_restaurante');
    }

    public function exibirInfoRestaurante()
    {
        $idRes = Auth::guard('estabelecimento')->id();

        $cadastro = DB::table('estabelecimentos')
        ->where('id_estab', $idRes)->get();

        return view('info_restaurante', compact('cadastro'));
    }

    public function alteraCadastro(Request $request)
    {
        $idRes = Auth::guard('estabelecimento')->id();

        // Validação dos dados
        $request->validate([
            'telefone' => ['required', new validaCelular],
            'email' => ['required', 'email', 'max:100', 'unique:estabelecimentos,email,' . $idRes . ',id_estab',],
        ]);

        // Capturando os dados validados
        $telefone = $request->input('telefone');
        $email = $request->input('email');

        // Atualizando os dados no modelo
        Estabelecimento::atualizarEstabelecimento($idRes, $telefone, $email);

        return redirect()->back()->with('success', 'Usuário atualizado com sucesso!');
    }

    public function exibirProdutosRestaurante()
    {
        $idRes = Auth::guard('estabelecimento')->id();

        $produtos = DB::table('produtos')
        ->join('categorias_produtos', 'produtos.id_categoria', '=', 'categorias_produtos.id_categoria')
        ->where('produtos.id_estab', $idRes)
        ->select('produtos.*', 'categorias_produtos.descricao as categoria_descricao')
        ->get();

        // Obter as categorias dos produtos
        $categorias = DB::table('categorias_produtos')->get();

        return view('produtos_restaurante', compact('produtos', 'categorias'));
    }

    public function cadastrarProduto(Request $request)
    {
        // Validação dos dados do formulário
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0.01',
            'id_categoria' => 'required|exists:categorias_produtos,id_categoria',
            'qtd_estoque' => 'required|integer|min:0',
            'imagem_produto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Verifica se o arquivo foi enviado
        if ($request->hasFile('imagem_produto')) {
            // Gera um nome único para a imagem
            $imagemNome = time() . '_' . $request->file('imagem_produto')->getClientOriginalName();

            // Move a imagem para a pasta public/imagem_produto
            $request->file('imagem_produto')->move(public_path('imagem_produto'), $imagemNome);
        } else {
            $imagemNome = 'sem_foto.png'; // Definir uma imagem padrão caso nenhuma seja enviada
        }

        // Inserção do produto na tabela 'produtos'
        DB::table('produtos')->insert([
            'nome' => $validated['nome'],
            'descricao' => $validated['descricao'],
            'valor' => $validated['valor'],
            'id_categoria' => $validated['id_categoria'],
            'qtd_estoque' => $validated['qtd_estoque'],
            'imagem_produto' => $imagemNome,
            'id_estab' => Auth::guard('estabelecimento')->id(), // Estabelecimento do usuário logado
        ]);

        // Redirecionar de volta com uma mensagem de sucesso
        return redirect()->route('produtos_restaurante')->with('success', 'Produto cadastrado com sucesso!');
    }

    public function atualizarProduto(Request $request)
    {
        $produto = DB::table('produtos')->where('id_produto', $request->id_produto)->first();

        // Se uma nova imagem foi enviada, faz o upload
        if ($request->hasFile('imagem_produto')) {
            $imagem = $request->file('imagem_produto');
            $nomeImagem = time() . '.' . $imagem->getClientOriginalExtension();
            $imagem->move(public_path('imagem_produto'), $nomeImagem);
        } else {
            // Se nenhuma nova imagem foi enviada, mantém a existente
            $nomeImagem = $produto->imagem_produto;
        }

        // Atualiza os dados no banco
        DB::table('produtos')
            ->where('id_produto', $request->id_produto)
            ->update([
                'nome' => $request->nome,
                'descricao' => $request->descricao,
                'valor' => $request->valor,
                'id_categoria' => $request->id_categoria,
                'qtd_estoque' => $request->qtd_estoque,
                'imagem_produto' => $nomeImagem,
            ]);

        return redirect()->back()->with('success', 'Produto atualizado com sucesso!');
    }

    public function exibirEstoqueRestaurante()
    {
        $idRes = Auth::guard('estabelecimento')->id();

        $produtos = DB::table('produtos')
        ->where('id_estab', $idRes)
        ->get();

        return view('estoque_restaurante', compact('produtos'));
    }

    public function atualizarEstoque(Request $request, $id)
    {
        $request->validate([
            'qtd_estoque' => 'required|integer|min:0'
        ]);

        // Atualiza diretamente no banco sem precisar recuperar o objeto
        DB::table('produtos')
            ->where('id_produto', $id)
            ->update(['qtd_estoque' => $request->qtd_estoque]);

        return redirect()->back()->with('success', 'Estoque atualizado com sucesso!');
    }

    public function exibirDashboardRestaurante()
    {
        $idEstab = Auth::guard('estabelecimento')->id();

        // Obter o total de clientes
        $clientes = DB::select('CALL clientes_por_estabelecimento(?)', [$idEstab]);
        $totalClientes = !empty($clientes) ? count($clientes) : 0;

        // Obter o total de pedidos do estabelecimento
        $pedidos = DB::select('CALL exibir_pedidos_f_estabelecimento(?)', [$idEstab]);
        $totalPedidos = !empty($pedidos) ? count($pedidos) : 0;

        // Obter o prato mais vendido
        $produtosMaisPopulares = DB::select('CALL exibir_produtos_mais_populares_por_estabelecimento(?)', [$idEstab]);
        $pratoMaisVendido = !empty($produtosMaisPopulares) ? $produtosMaisPopulares[0] : null;

        // Obter faturamento mensal
        $faturamentoMensalData = DB::select('CALL faturamento_estabelecimento(?)', [$idEstab]);
        $faturamentoMensal = collect($faturamentoMensalData)->firstWhere('mes', now()->month);
        $faturamentoMensal = $faturamentoMensal ? $faturamentoMensal->faturamento : 0;

        // Obter pedidos finalizados e cancelados por mês
        $pedidosPorMes = DB::select('CALL contagem_pedidos_f_mes(?)', [$idEstab]) ?? [];
        $canceladosPorMes = DB::select('CALL contagem_pedidos_c_mes(?)', [$idEstab]) ?? [];

        // Obter categorias populares
        $categoriasPopulares = DB::select('CALL exibir_categorias_mais_populares_por_estabelecimento(?)', [$idEstab]) ?? [];

        $avaliacao = DB::select('CALL calcular_media_avaliacoes(?)', [$idEstab]);

        // Preparar os dados para a view
        $data = [
            'total_clientes' => $totalClientes,
            'total_pedidos' => $totalPedidos,
            'prato_mais_vendido' => $pratoMaisVendido ? $pratoMaisVendido->produto : 'Nenhum prato vendido',
            'faturamento_mensal' => number_format($faturamentoMensal, 2, ',', '.'),
            'pratos_labels' => $pratoMaisVendido ? [$pratoMaisVendido->produto] : [],
            'pratos_vendas' => $pratoMaisVendido ? [$pratoMaisVendido->total_vendas] : [],
            'pedidos_por_mes' => [],
            'cancelados_por_mes' => [],
            'produtos_populares' => [],
            'categorias_populares' => [],
            'faturamento' => [],
        ];

        foreach ($pedidosPorMes as $item) {
            $data['pedidos_por_mes'][] = [
                'mes' => $item->mes ?? 0,
                'ano' => $item->ano ?? 0,
                'total_pedidos' => $item->total_pedidos ?? 0,
            ];
        }

        foreach ($canceladosPorMes as $item) {
            $data['cancelados_por_mes'][] = [
                'mes' => $item->mes ?? 0,
                'ano' => $item->ano ?? 0,
                'total_pedidos' => $item->total_pedidos ?? 0,
            ];
        }

        foreach ($produtosMaisPopulares as $produto) {
            $data['produtos_populares'][] = [
                'nome_produto' => $produto->produto ?? 'Desconhecido',
                'total_vendas' => $produto->total_vendas ?? 0,
            ];
        }

        foreach ($categoriasPopulares as $categoria) {
            $data['categorias_populares'][] = [
                'nome_categoria' => $categoria->descricao ?? 'Desconhecida',
                'total_vendas' => $categoria->total_vendas ?? 0,
            ];
        }

        foreach ($faturamentoMensalData as $faturamento) {
            $data['faturamento'][] = [
                'ano' => $faturamento->ano ?? 0,
                'mes' => $faturamento->mes ?? 0,
                'faturamento' => $faturamento->faturamento ?? 0,
            ];
        }

        return view('dashboard_restaurante', compact('data', 'avaliacao'));
    }

    public function esqueceuSenhaEstabelecimento(Request $request)
    {
        // Corrigido para corresponder ao campo correto
        $email = $request->input('emailResetSenhaEstab');

        // Buscar o cliente pelo email no banco de dados
        $estabelecimento = Estabelecimento::where('email', $email)->first();

        // Verificar se o cliente foi encontrado
        if ($estabelecimento) {
            // Gerar um token para redefinição de senha
            $token = Str::random(60);

            // Inserir o token no banco de dados para esse email
            ResetSenha::create([
                'id_usuario' => $estabelecimento->id_estab,
                'tipo_usuario' => 'estabelecimento',
                'email' => $estabelecimento->email,
                'criado_em' => now(),
                'token' => $token,
            ]);

            // Enviar o email de redefinição de senha
            Mail::to($estabelecimento->email)->send(new ResetSenhaEmail($estabelecimento, $token, 'estabelecimento'));

            return redirect()->back()->with('status', 'Email de redefinição de senha enviado!');
        } else {
            return redirect()->back()->with('error', 'Email não encontrado');
        }
    }


    public function resetSenhaEstabelecimento(Request $request)
    {
        $email = $request->query('email');
        $token = $request->query('token');

        if (!$email || !$token) {
            return redirect()->route('index')->with('error', 'Acesso inválido.');
        }

        $resetRecord = ResetSenha::where('email', $email)->where('token', $token)->first();

        if (!$resetRecord) {
            return redirect()->route('index_restaurante')->with('error', 'Link de redefinição de senha inválido ou expirado.');
        }

        $estabelecimento = Estabelecimento::where('email', $email)->first();

        if (Carbon::parse($resetRecord->criado_em)->addMinutes(1)->isPast()) {
            LogsToken::create([
                'id_usuario' => $estabelecimento->id_estab,
                'email' => $resetRecord->email,
                'motivo' => 'token expirado - redefinição de senha',
                'tipo_usuario' => 'estabelecimento',
                'token' => $resetRecord->token,
                'criado_em' => $resetRecord->criado_em,
                'usado_em' => now(),
            ]);

            ResetSenha::where('email', $email)->where('tipo_usuario', 'estabelecimento')->delete();

            return redirect()->route('index_restaurante')->with('error', 'O link de redefinição de senha expirou.');
        }

        session(['email' => $email, 'token' => $token]);

        return view('nova_senhaEstab', compact('token', 'email'));
    }

    public function definirNovaSenhaEstabelecimento(Request $request)
    {
        // Valida a entrada
        $request->validate([
            'new_password' => 'required|min:8', // Adicione outras regras de validação conforme necessário
        ],[
            'new_password.min' => 'Sua senha deve ter pelo menos 8 caracteres.'
        ]);

        // Obtém o email da sessão
        $email = session('email');

        // Verifique se o token é válido e se o email existe na tabela resets_senha_clientes
        $resetRecord = ResetSenha::where('email', $email)->first();

        if (!$resetRecord) {
            return redirect()->route('index_restaurante')->with('error', 'Link de redefinição de senha inválido ou expirado.');
        }else {

            // Busca o cliente pelo ID
            $estabelecimento = Estabelecimento::where('email', $email)->first();

            LogsToken::create([
                'id_usuario' => $estabelecimento->id_estab,
                'email' => $resetRecord->email,
                'motivo' => 'redefinição de senha',
                'tipo_usuario' => 'estabelecimento',
                'token' => $resetRecord->token,
                'criado_em' => $resetRecord->criado_em,
                'usado_em' => now(),
            ]);

            ResetSenha::where('email', $email)->where('tipo_usuario', 'estabelecimento')->delete();

            // Atualiza a senha
            $estabelecimento->senha = Hash::make($request->input('new_password'));
            $estabelecimento->save();

            return redirect()->route('index_restaurante')->with('success', 'Senha redefinida com sucesso');
        }
    }

    public function calcularMediaAvaliacao()
    {
        return view('dashboard_restaurante', compact('data'));
    }

    public function exibirPlanosdRestaurante(){
        $idEstab = Auth::guard('estabelecimento')->id();

        // Buscar o plano ativo do estabelecimento, se houver
        $planoAtivo = DB::table('planos_estabelecimentos')
            ->join('planos', 'planos.id_plano', '=', 'planos_estabelecimentos.id_plano')
            ->where('planos_estabelecimentos.id_estab', $idEstab)
            ->where('planos_estabelecimentos.ativo', 1)
            ->select('planos.*')
            ->first();

        // Buscar todos os planos disponíveis
        $todosPlanos = DB::table('planos')->get();

        // Filtrar os planos disponíveis (excluindo o ativo, se houver)
        $planosDisponiveis = $todosPlanos->filter(function ($plano) use ($planoAtivo) {
            return !$planoAtivo || $plano->id_plano !== $planoAtivo->id_plano;
        });

        return view('planos_restaurante', compact('planoAtivo', 'planosDisponiveis'));
    }

    public function escolherPlano(Request $request)
    {
        $idEstab = Auth::guard('estabelecimento')->id();
        $idPlano = $request->input('id_plano'); // ID do plano selecionado

        try {
            // Chama o procedure para atualizar o plano
            DB::statement("CALL escolher_plano(?, ?)", [$idEstab, $idPlano]);

            return redirect()->back()->with('success', 'Plano atualizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao escolher o plano.');
        }
    }

    public function exibirChamados()
    {
        $idEstab = Auth::guard('estabelecimento')->id();

        // Seleciona as últimas mensagens de cada chat, agrupando pelo id_chat
        $mensagens = DB::table('mensagens_estab')
        ->where('id_remetente', $idEstab)
        ->orWhere('id_destinatario', $idEstab)
        ->orderBy('data_envio', 'desc')  // Ordena por data de envio para pegar a última mensagem
        ->get()
        ->groupBy('id_chat')  // Agrupa as mensagens pelo id_chat
        ->map(function ($mensagensChat) {
            return $mensagensChat->first();  // Pega a primeira mensagem de cada grupo (última mensagem do chat)
        });

        $categorias = DB::table('categorias_chamado')
        ->get();

        return view('chamados_estab', compact('mensagens', 'categorias', 'idEstab'));
    }

    public function buscarMensagens($idChat)
    {
        $idEstab = Auth::guard('estabelecimento')->id();

        // Busca todas as mensagens do chat específico
        $mensagens = DB::table('mensagens_estab')
            ->where('id_chat', $idChat)
            ->orderBy('data_envio', 'asc')
            ->get();

        return response()->json($mensagens);
    }

    public function abrirChamado(Request $request)
    {
        $idEstab = auth()->guard('estabelecimento')->id();
        $idChat = (string) Str::uuid();

        // Criando a mensagem
        $mensagens = MensagensEstab::create([
            'id_chat' => $idChat,
            'id_remetente' => $idEstab,
            'id_destinatario' => 1,
            'categoria' => $request->categoria,
            'mensagem' => $request->mensagem,
            'data_envio' => now(),
            'ativo' => 1,
        ]);

        return redirect()->back()->with('success', 'Mensagem enviada com sucesso!');
    }

    public function responderChamado(Request $request)
    {
        $idEstab = auth()->guard('estabelecimento')->id();
        $chatId = $request->input('id_chat');
        $resposta = $request->input('resposta');

        // Busca a última mensagem do chat para descobrir o destinatário
        $ultimaMensagem = MensagensEstab::where('id_chat', $chatId)
            ->orderBy('data_envio', 'desc')
            ->first();

        // Se não houver mensagens no chat, atribui o próprio admin como destinatário
        if (!$ultimaMensagem) {
            $idDestinatario = 1;  // Caso não exista histórico, o destinatário pode ser o admin ou qualquer outra lógica
        } else {
            // Verifica quem é o destinatário da última mensagem
            $idDestinatario = ($ultimaMensagem->id_remetente == Auth::id())
                ? $ultimaMensagem->id_destinatario
                : $ultimaMensagem->id_remetente;

            $categoria = $ultimaMensagem->categoria;
        }

        // Cria uma nova mensagem no banco de dados
        $novaMensagem = new MensagensEstab();
        $novaMensagem->id_chat = $chatId;
        $novaMensagem->id_remetente = $idEstab;  // O remetente é o usuário logado
        $novaMensagem->id_destinatario = $idDestinatario;  // O destinatário é o oposto da última mensagem
        $novaMensagem->categoria = $categoria;
        $novaMensagem->mensagem = $resposta;
        $novaMensagem->data_envio = now();  // A data de envio é a hora atual
        $novaMensagem->ativo = 1;
        $novaMensagem->save();

        // Redireciona ou retorna uma resposta para o usuário
        return redirect()->back()->with('success', 'Resposta enviada com sucesso!');
    }

    public function uploadImagemPerfil(Request $request)
    {
        // Validação da imagem
        $request->validate([
            'imagem_perfil' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'imagem_perfil.image' => 'Os formatos aceitos são .jpeg, .png e .jpg'
        ]);

        // Verifica se o arquivo foi enviado
        if ($request->hasFile('imagem_perfil')) {
            // Recupera o usuário autenticado
            $user = auth()->user();

            // Se o usuário já possui uma imagem de perfil, exclui a imagem antiga
            if ($user->imagem_perfil) {
                $oldImagePath = storage_path('app/public/imagem_perfil/' . $user->imagem_perfil);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Salva a nova imagem diretamente em public/imagem_perfil e obtém o nome do arquivo
            $imagemNome = time() . '_' . $request->file('imagem_perfil')->getClientOriginalName();
            $request->file('imagem_perfil')->move(public_path('imagem_perfil'), $imagemNome);

            // Salva o nome do arquivo da nova imagem no banco de dados
            $user->update(['imagem_perfil' => $imagemNome]);

            return back()->with('success', 'Foto de perfil atualizada!');
        }

        return back()->with('error', 'Erro ao fazer upload da foto de perfil.');
    }

    public function exibirGradeHorario()
    {
        $idEstab = auth()->guard('estabelecimento')->id();

        $horarios = DB::select('CALL consulta_grade_horaria(?)', [$idEstab]);

        return view('grade_horario', compact('horarios'));
    }

    // Método para deletar horário
    public function deletarHorario($id)
    {
        // Lógica para encontrar e deletar o horário pelo ID
        $horario = DB::table('grades_horario')->where('id_grade', $id)->first();

        if ($horario) {
            DB::table('grades_horario')->where('id_grade', $id)->delete();
            return redirect()->back()->with('success', 'Horário deletado com sucesso.');
        }

        return redirect()->back()->with('error', 'Horário não encontrado.');
    }

    public function salvarGrade(Request $request)
    {
        // Valida os dados enviados pelo modal
        $validatedData = $request->validate([
            'dia_semana' => 'required|string|max:10',
            'inicio_expediente' => 'required|string|max:255', //verificar a possibilidade, necessidade de mudar o tipo de dados
            'termino_expediente' => 'required|string|max:255', //verificar a possibilidade, necessidade de mudar o tipo de dados
        ]);

        $idEstab = Auth::guard('estabelecimento')->id();

        // Verifica se já existe uma grade cadastrada para o mesmo dia da semana
        $gradeExistente = GradeHorario::where('id_estab', $idEstab)
        ->where('dia_semana', $validatedData['dia_semana'])->first();

        if ($gradeExistente) {
            $gradeExistente->update([
                'inicio_expediente' => $validatedData['inicio_expediente'],
                'termino_expediente' => $validatedData['termino_expediente']
            ]);

            return redirect()->back()->with('success', 'Horário atualizado!');
        }

        // Cria a grade
        GradeHorario::create([
            'id_estab' => $idEstab,
            'dia_semana' => $validatedData['dia_semana'],
            'inicio_expediente' => $validatedData['inicio_expediente'],
            'termino_expediente' => $validatedData['termino_expediente']
        ]);

        return redirect()->back()->with('success', 'Grade cadastrada com sucesso!');
    }

    public function alterarSenha()
    {
        $idRes = Auth::guard('estabelecimento')->id();

        $cadastro = DB::table('estabelecimentos')
        ->where('id_estab', $idRes)->get();

        return view('alterar_senhaEstab', compact('cadastro'));
    }

    public function confirmarSenha(Request $request)
    {
        $request->validate([
            'senhaAntiga' => 'required',
            'novaSenha' => 'required|min:8',
            'confirmarSenha' => 'required|same:novaSenha',
        ]);

        $idRes = Auth::guard('estabelecimento')->id();

        $restaurante = DB::table('estabelecimentos')->where('id_estab', $idRes)->first();

        if (!$restaurante) {
            return back()->with('error', 'Usuário não encontrado.');
        }

        if (!Hash::check($request->senhaAntiga, $restaurante->senha)) {
            return back()->with('error', 'Senha atual incorreta.');
        }

        DB::table('estabelecimentos')->where('id_estab', $idRes)->update([
            'senha' => Hash::make($request->novaSenha)
        ]);

        return back()->with('success', 'Senha alterada com sucesso!');
    }

    public function logoutEstabelecimento(Request $request)
    {
        Auth::guard('estabelecimento')->logout();

        return redirect()->route('index')->with('success', 'Logout realizado com sucesso!');
    }


}
