<?php

namespace Emagine\RPG\Model;

class AcaoInfo
{
    const EVADIR = "evadir";
    const PEGAR_ARMA_CHAO = "pegar-arma-chao";
    const PEGAR_ARMA_COLDRI = "pegar-arma-coldri";
    const GUARDAR_ARMA = "guardar-arma";
    const SOLTAR_ARMA = "soltar-arma";
    const SOCO = "soco";
    const CHUTE = "chute";
    const TURNO = "turno";

    const POSICAO_LEVANTAR = "levantar";
    const POSICAO_AGACHAR = "agachar";
    const POSICAO_DEITAR = "deitar";

    const GRUPO_MAO_DIREITA = "mao-direita";
    const GRUPO_MAO_ESQUERDA = "mao-esquerda";
    const GRUPO_CHUTE = "chute";
    const GRUPO_POSICAO = "posicao";
    const GRUPO_MOVIMENTO = "movimento";
    const GRUPO_TURNO = "turno";

    private $slug;
    private $nome;
    private $grupo;
    private $descricao;
    private $nh;
    private $tipo;
    private $dano;
    private $item_slug;
    private $local;
    private $ativo;
    private $pontosImpacto = array();

    /**
     * @return string
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * @param string $value
     */
    public function setSlug($value) {
        $this->slug = $value;
    }

    /**
     * @return string
     */
    public function getNome() {
        return $this->nome;
    }

    /**
     * @param string $value
     */
    public function setNome($value) {
        $this->nome = $value;
    }

    /**
     * @return string
     */
    public function getGrupo() {
        return $this->grupo;
    }

    /**
     * @param string $value
     */
    public function setGrupo($value) {
        $this->grupo = $value;
    }

    /**
     * @return string
     */
    public function getDescricao() {
        return $this->descricao;
    }

    /**
     * @param string $value
     */
    public function setDescricao($value) {
        $this->descricao = $value;
    }

    /**
     * @return int
     */
    public function getNH() {
        return $this->nh;
    }

    /**
     * @param int $value
     */
    public function setNH($value) {
        $this->nh = $value;
    }

    /**
     * @return string
     */
    public function getTipo() {
        return $this->tipo;
    }

    /**
     * @param string $value
     */
    public function setTipo($value) {
        $this->tipo = $value;
    }

    /**
     * @return DanoInfo
     */
    public function getDano() {
        return $this->dano;
    }

    /**
     * @param DanoInfo $value
     */
    public function setDano($value) {
        $this->dano = $value;
    }

    /**
     * @return string
     */
    public function getItemSlug() {
        return $this->item_slug;
    }

    /**
     * @param string $value
     */
    public function setItemSlug($value) {
        $this->item_slug = $value;
    }

    /**
     * @return string
     */
    public function getLocal() {
        return $this->local;
    }

    /**
     * @param string $value
     */
    public function setLocal($value) {
        $this->local = $value;
    }

    /**
     * @return bool
     */
    public function getAtivo() {
        return $this->ativo;
    }

    /**
     * @param bool $value
     */
    public function setAtivo($value) {
        $this->ativo = $value;
    }

    /**
     * @return string
     */
    public function getDanoStr() {
        $str = "";
        if (!is_null($this->getDano())) {
            $str = $this->getDano()->getDanoStr() . " " . $this->getTipoStr();
        }
        return $str;
    }

    /**
     * @return AcaoLocalInfo[]
     */
    public function listarPontoImpacto() {
        return $this->pontosImpacto;
    }

    /**
     * @param string $pontoImpacto
     * @return AcaoLocalInfo|null
     */
    public function getPontoImpacto($pontoImpacto) {
        $pontosImpacto = $this->listarPontoImpacto();
        if (array_key_exists($pontoImpacto, $pontosImpacto)) {
            return $pontosImpacto[$pontoImpacto];
        }
        return null;
    }

    /**
     * @param AcaoLocalInfo[] $locais
     */
    public function setPontoImpacto($locais) {
        $this->pontosImpacto = $locais;
    }

    /**
     * @param AcaoLocalInfo $pontoImpacto
     */
    public function adicionarPontoImpacto($pontoImpacto) {
        $this->pontosImpacto[$pontoImpacto->getLocal()] = $pontoImpacto;
    }

    /**
     * @param string $pontoImpacto
     */
    public function removerPontoImpacto($pontoImpacto) {
        unset($this->pontosImpacto[$pontoImpacto]);
    }

    /**
     * @return string
     */
    public function getTipoStr() {
        $str = "";
        switch ($this->getTipo()) {
            case ItemAtributoInfo::CONTUSAO:
                $str = "contusão";
                break;
            case ItemAtributoInfo::CORTE:
                $str = "corte";
                break;
            case ItemAtributoInfo::PERFURACAO:
                $str = "perfuração";
                break;
        }
        return $str;
    }
}