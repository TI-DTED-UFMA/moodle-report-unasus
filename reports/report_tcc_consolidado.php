<?php
/**
 * Created by PhpStorm.
 * User: salazar
 * Date: 12/02/14
 * Time: 15:45
 */

class report_tcc_consolidado {

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
        $result_array = loop_atividades_e_foruns_sintese(null, null, null, null, true);

        /* Retorno da função loop_atividades */
        $total_alunos = $result_array['total_alunos'];
        $lista_atividade = $result_array['lista_atividade'];
        $associativo_atividade = $result_array['associativo_atividade'];


        /* Variaveis totais do relatorio */
        $total_nao_acessadas = new dado_somatorio_grupo();
        $total_tcc_completo = new dado_somatorio_grupo();

        $total_abstract = new dado_somatorio_grupo();
        $total_presentation = new dado_somatorio_grupo();
        $total_final_considerations = new dado_somatorio_grupo();

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
                        $bool_atividades[$id]['abstract'] = false;
                        $bool_atividades[$id]['presentation'] = false;
                        $bool_atividades[$id]['final_considerations'] = false;
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
                    } else { //não foi avaliada
                        /* Atividade nao completa entao tcc nao esta completo */
                        $bool_atividades[$id]['tcc_completo'] = false;
                    }

                    /* Verificar não acessado */
                    if ($dado_atividade->status != 'new') {
                        $bool_atividades[$id]['nao_acessado'] = false;
                    }
                }

                $chapter = 'abstract';

                for($i=0; $i<=2; $i++){
                    if($aluno[0]->has_evaluated_chapters($chapter)){
                        switch($chapter){
                            case 'abstract': $total_abstract->inc($grupo_id, $id);
                                break;
                            case 'presentation': $total_presentation->inc($grupo_id, $id);
                                break;
                            case 'final_considerations': $total_final_considerations->inc($grupo_id, $id);
                                break;
                        }
                    }
                    $chapter = ($i == 0) ? 'presentation' : 'final_considerations';
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
            /* Coluna nome orientador */

            $data = array();
            $data[] = grupos_tutoria::grupo_orientacao_to_string($factory->get_curso_ufsc(), $grupo_id);

            /* Grupo vazio, imprimir apenas o nome do tutor */
            if (empty($grupo)) {
                $dados[] = $data;
                continue;
            }

            foreach ($grupo as $ltiid => $lti) {
                if(isset($total_alunos[$grupo_id])){
                    $lti['acessado'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_nao_acessadas->get($grupo_id, $ltiid));
                    $lti['tcc'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_tcc_completo->get($grupo_id, $ltiid));
                    $lti['abstract'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_abstract->get($grupo_id, $ltiid));
                    $lti['presentation'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_presentation->get($grupo_id, $ltiid));
                    $lti['final_considerations'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_final_considerations->get($grupo_id, $ltiid));

                    /* Preencher relatorio */
                    foreach ($lti as $id => $dado_atividade) {
                        /* Coluna não acessado e concluído para cada modulo dentro do grupo */
                        if ($dado_atividade instanceof dado_atividades_alunos) {
                            $data[] = $dado_atividade;

                            $total_atividades_concluidos->add($ltiid, $id, $dado_atividade->get_count());
                            $total_atividades_alunos->add($ltiid, $id, $dado_atividade->get_total());
                        }
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
        $header = get_table_header_tcc_portfolio_entrega_atividades(true);

        foreach ($header as $key => $modulo) {
            array_push($modulo, 'Não acessado');
            array_push($modulo, 'Concluído');
            array_push($modulo, 'Resumo');
            array_push($modulo, 'Introdução');
            array_push($modulo, 'Considerações Finais');

            $header[$key] = $modulo;
        }

        return $header;
    }

} 