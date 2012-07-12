<?php

// chamada do arquivo de filtro
require_once($CFG->dirroot . '/report/unasus/filter.php');

defined('MOODLE_INTERNAL') || die();

class report_unasus_renderer extends plugin_renderer_base {

    private $report;

    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->report = optional_param('relatorio', null, PARAM_ALPHANUMEXT);
    }

    /**
     * Cria o cabeçalho padrão para os relatórios
     *
     * @param String $title titulo para a página
     * @return String cabeçalho, título da página e barra de filtragem
     */
    public function default_header($title = null) {
        $output = $this->header();
        $output .= $this->heading($title);

        //barra de filtro
        $form_attributes = array('class' => 'filter_form');
        $filter_form = new filter_tutor_polo(null, array('relatorio' => $this->report), 'post', '', $form_attributes);

        $output .= get_form_display($filter_form);
        return $output;
    }

    /**
     * @return Form barra lateral de navegação e footer
     */
    public function default_footer() {
        return $this->footer();
    }

    /**
     * Cria a tabela dos relatorios, a aplicacao do css irá depender de qual foi
     * o relatório que invocou esta funcao
     * @TODO construir uma simple table que não necessita ter divisões de tutor/polo barra azul
     * @param Array $dadostabela dados para alimentar a tabela
     * @param Array $header header para a tabela, pode ser um
     *              array('value1','value2','value3') ou um array de chaves valor
     *              array('modulo'=> array('value1','value2'))
     * @return html_table
     */
    public function default_table($dadostabela, $header) {
        //criacao da tabela
        $table = new report_unasus_table();
        $table->attributes['class'] = "relatorio-unasus $this->report generaltable";
        $table->tablealign = 'center';

        $header_keys = array_keys($header);
        if (is_array($header[$header_keys[0]])) { // Double Header
            $table->build_double_header($header);
        } else {
            $table->build_single_header($header);
        }

        foreach ($dadostabela as $tutor => $alunos) {

            //celula com o nome do tutor, a cada iteração um tutor e seus respectivos
            //alunos vao sendo populado na tabela
            $cel_tutor = new html_table_cell($tutor);
            $cel_tutor->attributes = array('class' => 'tutor');
            $cel_tutor->colspan = count($alunos[0]); // expande a célula com nome dos tutores
            $row_tutor = new html_table_row();
            $row_tutor->cells[] = $cel_tutor;
            $table->data[] = $row_tutor;

            //atividades de cada aluno daquele dado tutor
            foreach ($alunos as $aluno) {
                $row = new html_table_row();
                foreach ($aluno as $valor) {
                    if (is_a($valor, 'unasus_data')) {
                        $cell = new html_table_cell($valor);
                        $cell->attributes = array(
                            'class' => $valor->get_css_class());
                    } else { // Aluno
                        $cell = new html_table_cell($valor);
                        $cell->header = true;
                        $cell->attributes = array('class' => 'estudante');
                    }

                    $row->cells[] = $cell;
                }
                $table->data[] = $row;
            }
        }

        return $table;
    }

    /**
     * Cria a página referente ao relatorio atividade vs notas atribuidas
     * @return String
     */
    public function page_atividades_vs_notas_atribuidas() {
        $output = $this->default_header('Relatório de Atividades vs Notas Atribuídas');



        //Criação da tabela
        $table = $this->default_table(get_dados_dos_alunos(), get_header_modulo_atividade());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página referente ao relatorio de entrega de atividades
     * @return String
     */
    public function page_entrega_de_atividades() {
        $output = $this->default_header('Relatório de Acompanhamento de Entrega de Atividades');

        //Criação da tabela
        $table = $this->default_table(get_dados_entrega_atividades(), get_header_modulo_atividade());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página referente ao relatorio de acompanhamento de avaliacao de atividades
     * @return String
     */
    public function page_acompanhamento_de_avaliacao() {
        $output = $this->default_header('Relatório de Acompanhamento de Avaliação de Atividades');

        //Criação da tabela
        $table = $this->default_table(get_dados_acompanhamento_de_avaliacao(), get_header_modulo_atividade());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página referente ao relatorio de Atividades Postadas e não Avaliadas
     * @return String
     */
    public function page_atividades_nao_avaliadas() {
        $output = $this->default_header('Relatório de Atividades Postadas e Não Avaliadas');

        //Criação da tabela
        $table = $this->default_table(get_dados_atividades_nao_avaliadas(), get_header_modulo_atividade());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página referente ao Relatório de Estudantes sem Atividades Postadas (fora do prazo)
     * @return String
     */
    public function page_estudante_sem_atividade_postada() {
        $output = $this->default_header('Relatório de Estudantes sem Atividades Postadas (fora do prazo)');

        //Criação da tabela
        $table = $this->default_table(get_dados_estudante_sem_atividade_postada(), get_header_estudante_sem_atividade_postada());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página referente ao Relatório de Atividades com Avaliação em Atraso por Tutor
     * @return String
     */
    public function page_atividades_em_atraso_tutor() {
        $output = $this->default_header('Relatório de Atividades com Avaliação em Atraso por Tutor');

        //Criação da tabela
        $table = $this->default_table(get_dados_avaliacao_em_atraso_tutor(), get_header_modulo_atividade());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página referente ao Relatório de Atividades com Notas Atribuídas por Tutor
     * @param string $css_class classe css para aplicar na tabela
     * @return String
     */
    public function page_atividades_nota_atribuida_tutor() {
        $output = $this->default_header('Relatório de Atividades com Avaliação em Atraso por Tutor');

        //Criação da tabela
        $table = $this->default_table(get_dados_atividades_nota_atribuida_tutor(), get_header_modulo_atividade());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página referente ao Relatório de Acesso ao Moodle (período: X-Y)
     * @param string $css_class classe css para aplicar na tabela
     * @return String
     */
    public function page_acesso_tutor() {
        $output = $this->default_header('Relatório de Acesso ao Moodle');

        //Criação da tabela
        $table = $this->default_table(get_dados_acesso_tutor(), get_header_acesso_tutor());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página referente ao Relatório de uso do sistema pelo tutor
     * @param string $css_class classe css para aplicar na tabela
     * @return String
     */
    public function page_uso_sistema_tutor() {
        $output = $this->default_header('Relatório de uso do sistema pelo tutor');

        //Criação da tabela
        $table = $this->default_table(get_dados_uso_sistema_tutor(), get_header_uso_sistema_tutor());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página referente ao Relatório de potenciais evasões
     * @param string $css_class classe css para aplicar na tabela
     * @return String
     */
    public function page_potenciais_evasoes() {
        $output = $this->default_header('Relatório de potenciais evasões');

        //Criação da tabela
        $table = $this->default_table(get_dados_potenciais_evasoes(), get_header_modulo_atividade());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

}

