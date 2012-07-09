<?php

//
// Pessoas
//

/**
 * Estrutura de dados de Pessoas (Tutores, Estudantes)
 * Auxilia renderização nos relatórios.
 */
abstract class pessoa {
    protected $name;

    function __construct($name) {
        $this->name = $name;
    }
}


/**
 * Representa um estudante nos relatórios
 */
class estudante extends pessoa {

    function __toString() {
        $link = new moodle_url('/user/profile.php', array('id' => 1)); // mock id - visitante
        return html_writer::link($link, $this->name);
    }
}

/**
 * Representa um tutor nos relatórios
 */
class tutor extends pessoa {
    function __toString() {
        return $this->name;
    }
}

//
// Relatórios
//

/**
 * Estrutura para auxiliar a renderização dos dados dos relatórios
 */
abstract class unasus_data {
    public abstract function get_css_class();
}

class dado_atividade_vs_nota extends unasus_data {

    const ATIVIDADE_NAO_ENTREGUE = 0;
    const CORRECAO_ATRASADA = 1;
    const ATIVIDADE_AVALIADA = 2;
    const ATIVIDADE_NO_PRAZO_ENTREGA = 3;

    var $tipo;
    var $nota;
    var $atraso;

    function __construct($tipo, $nota = 0, $atraso = 0) {

        $this->tipo = $tipo;
        $this->nota = $nota;
        $this->atraso = $atraso;
    }

    public function __toString() {
        switch ($this->tipo) {
            case dado_atividade_vs_nota::ATIVIDADE_NAO_ENTREGUE:
                return 'Atividade não Entregue';
                break;
            case dado_atividade_vs_nota::CORRECAO_ATRASADA:
                return "$this->atraso dias";
                break;
            case dado_atividade_vs_nota::ATIVIDADE_AVALIADA:
                return (String)$this->nota;
                break;
            case dado_atividade_vs_nota::ATIVIDADE_NO_PRAZO_ENTREGA:
                return 'No prazo';
                break;
        }
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_atividade_vs_nota::ATIVIDADE_NAO_ENTREGUE:
                return 'nao_entregue';
            case dado_atividade_vs_nota::CORRECAO_ATRASADA:
                return ($this->atraso > 2) ? 'muito_atraso' : 'pouco_atraso';
            case dado_atividade_vs_nota::ATIVIDADE_AVALIADA:
                return 'nota_atribuida';
            case dado_atividade_vs_nota::ATIVIDADE_NO_PRAZO_ENTREGA:
                return 'nao_realizada';
            default:
                return '';
        }
    }

}

class dado_entrega_atividade extends unasus_data {
    const ATIVIDADE_NAO_ENTREGUE = 0;
    const ATIVIDADE_ENTREGUE_NO_PRAZO = 1;
    const ATIVIDADE_ENTREGUE_FORA_DO_PRAZO = 2;

    var $tipo;
    var $atraso;

    function __construct($tipo, $atraso = 0) {
        $this->tipo = $tipo;
        $this->atraso = $atraso;
    }

    public function __toString() {
        switch ($this->tipo) {
            case dado_entrega_atividade::ATIVIDADE_NAO_ENTREGUE:
                return '';
                break;
            case dado_entrega_atividade::ATIVIDADE_ENTREGUE_NO_PRAZO:
                return '';
                break;
            case dado_entrega_atividade::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO:
                return "$this->atraso dias";
                break;
        }
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_entrega_atividade::ATIVIDADE_NAO_ENTREGUE:
                return 'nao_entregue';
                break;
            case dado_entrega_atividade::ATIVIDADE_ENTREGUE_NO_PRAZO:
                return 'no_prazo';
                break;
            case dado_entrega_atividade::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO:
                return ($this->atraso > 2) ? 'muito_atraso' : 'pouco_atraso';
                break;
        }
    }

}

class dado_acompanhamento_avaliacao extends unasus_data {

    const ATIVIDADE_NAO_ENTREGUE = 0;
    const CORRECAO_NO_PRAZO = 1;
    const CORRECAO_ATRASADA = 2;

    var $tipo;
    var $atraso;

    function __construct($tipo, $atraso = 0) {
        $this->tipo = $tipo;
        $this->atraso = $atraso;
    }

    public function __toString() {
        switch ($this->tipo) {
            case dado_acompanhamento_avaliacao::ATIVIDADE_NAO_ENTREGUE:
                return '';
                break;
            default:
                return "$this->atraso dias";
        }
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_acompanhamento_avaliacao::ATIVIDADE_NAO_ENTREGUE:
                return 'nao_entregue';
                break;
            case dado_acompanhamento_avaliacao::CORRECAO_NO_PRAZO:
                return 'no_prazo';
                break;
            case dado_acompanhamento_avaliacao::CORRECAO_ATRASADA:
                return ($this->atraso > 7) ? 'muito_atraso' : 'pouco_atraso';
                break;
        }
    }

}

?>