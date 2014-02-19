<?php

class report_tcc_portfolio extends Factory {

    function __construct() {
    }

    public function initialize($factory, $filtro = true) {
        $factory->mostrar_barra_filtragem = $filtro;
        $factory->mostrar_botoes_grafico = false; //Botões de geração de gráfico removidos - não são utilizados
        $factory->mostrar_botoes_dot_chart = false;
        $factory->mostrar_filtro_polos = true;
        $factory->mostrar_filtro_cohorts = true;
        $factory->mostrar_filtro_modulos = true;
        $factory->mostrar_filtro_intervalo_tempo = false;
        $factory->mostrar_aviso_intervalo_tempo = false;
    }

    public function render_report_default($renderer){
        echo $renderer->build_page();
    }

    public function render_report_table($renderer, $object, $factory = null) {
        $this->initialize($factory, false);
        echo $renderer->page_atividades_nao_avaliadas($object);
    }

    public function get_dados(){
        /* Factory */
        $factory = Factory::singleton();

        /* Resultados */
        $result_array = loop_atividades_e_foruns_sintese(null, null, null);

        /* Retorno da função loop_atividades */
        $total_alunos = $result_array['total_alunos'];
        $lista_atividade = $result_array['lista_atividade'];
        $associativo_atividade = $result_array['associativo_atividade'];

        /* Variaveis totais do relatorio */
        $total_nao_acessadas = new dado_somatorio_grupo();
        $total_tcc_completo = new dado_somatorio_grupo();

        /* Loop nas atividades para calcular os somatorios para sintese */
        foreach ($associativo_atividade as $grupo_id => $array_dados) {
            foreach ($array_dados as $aluno) {
                $bool_atividades = array();

                foreach ($aluno as $dado_atividade) {
                    /** @var report_unasus_data_lti $dado_atividade */
                    $id = $dado_atividade->source_activity->id;
                    if (!array_key_exists($id, $bool_atividades)) {
                        $bool_atividades[$id]['tcc_completo'] = true;
                        $bool_atividades[$id]['nao_acessado'] = true;
                        $bool_atividades[$id]['has_activity'] = false;
                    }

                    // Não se aplica para este estudante
                    if ($dado_atividade instanceof report_unasus_data_empty) {
                        continue;
                    }
                    $bool_atividades[$id]['has_activity'] = true;

                    /* Verificar se atividade foi avaliada */
                    if ($dado_atividade->has_evaluated()) {
                        if ($dado_atividade instanceof report_unasus_data_lti) {
                            /** @var dado_atividades_alunos $dado */

                            $dado =& $lista_atividade[$grupo_id][$id][$dado_atividade->source_activity->position];
                            $dado->incrementar();
                        }
                    } else {
                        /* Atividade nao completa entao tcc nao esta completo */
                        $bool_atividades[$id]['tcc_completo'] = false;
                    }

                    /* Verificar não acessado */
                    if ($dado_atividade->status != 'new') {
                        $bool_atividades[$id]['nao_acessado'] = false;
                    }
                }
                foreach($bool_atividades as $id => $bool_atividade) {
                    $total_tcc_completo->inc($grupo_id, $id, $bool_atividade['has_activity'] && $bool_atividade['tcc_completo']);
                    $total_nao_acessadas->inc($grupo_id, $id, $bool_atividade['has_activity'] && $bool_atividade['nao_acessado']);
                }
            }
        }

        $dados = array();
        $total_atividades_concluidos = new dado_somatorio_grupo();
        $total_atividades_alunos = new dado_somatorio_grupo();

        foreach ($lista_atividade as $grupo_id => $grupo) {
            /* Coluna nome grupo tutoria */
            $data = array();
            $data[] = grupos_tutoria::grupo_tutoria_to_string($factory->get_curso_ufsc(), $grupo_id);

            /* Grupo vazio, imprimir apenas o nome do tutor */
            if (empty($grupo)) {
                $dados[] = $data;
                continue;
            }

            foreach ($grupo as $ltiid => $lti) {
                /* Inserir mais 2 colunas de atividades no array do grupo para ser preenchido no foreach do lti */
                $lti['acessado'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_nao_acessadas->get($grupo_id, $ltiid));
                $lti['tcc'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_tcc_completo->get($grupo_id, $ltiid));

                /* Preencher relatorio */
                foreach ($lti as $id => $dado_atividade) {
                    /* Coluna não acessado e tcc para cada modulo dentro do grupo */
                    if ($dado_atividade instanceof dado_atividades_alunos) {
                        $data[] = $dado_atividade;

                        $total_atividades_concluidos->add($ltiid, $id, $dado_atividade->get_count());
                        $total_atividades_alunos->add($ltiid, $id, $dado_atividade->get_total());
                    }
                }
            }
            $dados[] = $data;
        }
        /* Linha total alunos com atividades concluidas  */
        $data_total = array(new dado_texto(html_writer::tag('strong', 'Total por curso'), 'total'));
        $count_alunos = $total_atividades_alunos->get();

        foreach ($total_atividades_concluidos->get() as $ltiid => $lti) {
            foreach ($lti as $id => $count) {
                $data_total[] = new dado_atividades_total($count_alunos[$ltiid][$id], $count);
            }
        }
        array_unshift($dados, $data_total);

        return $dados;
    }

    public function get_table_header(){
        $header = get_table_header_tcc_portfolio_entrega_atividades();

        foreach ($header as $key => $modulo) {
            array_push($modulo, 'Não acessado');
            array_push($modulo, 'Concluído');

            $header[$key] = $modulo;
        }

        return $header;

    }


}