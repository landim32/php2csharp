<?php
namespace Emagine\RPG\BLL;

use Exception;
use Landim32\EasyDB\DB;
use Emagine\RPG\DAL\CombateDAL;
use Emagine\RPG\DAL\CombateMembroDAL;
use Emagine\RPG\DAL\CombatePersonagemDAL;
use Emagine\RPG\DAL\CombateLogDAL;
use Emagine\RPG\DAL\CombateObjetoChaoDAL;
use Emagine\RPG\Model\CombateInfo;
use Emagine\RPG\Model\AcaoInfo;
use Emagine\RPG\Model\AcaoLocalInfo;
use Emagine\RPG\Model\CombateLogInfo;
use Emagine\RPG\Model\CombateMembroInfo;
use Emagine\RPG\Model\CombateObjetoChaoInfo;
use Emagine\RPG\Model\CombatePersonagemInfo;
use Emagine\RPG\Model\DanoInfo;
use Emagine\RPG\Model\ItemAtributoInfo;
use Emagine\RPG\Model\ItemInfo;
use Emagine\RPG\Model\PericiaInfo;
use Emagine\RPG\Model\PersonagemInfo;

/**
 * Class CombateBLL
 * @package EmagineRPG\BLL
 * @tablename combate
 * @author EmagineCRUD
 */
class CombateBLL {

	/**
     * @throws Exception
     * @param int $id_personagem
     * @param int|null $cod_situacao
	 * @return CombateInfo[]
	 */
	public function listar($id_personagem, $cod_situacao = null) {
		$dal = new CombateDAL();
		return $dal->listar($id_personagem, $cod_situacao);
	}

	/**
     * @throws Exception
	 * @param int $id_combate
	 * @return CombateInfo
	 */
	public function pegar($id_combate) {
		$dal = new CombateDAL();
		return $dal->pegar($id_combate);
	}

	/**
	 * @throws Exception
	 * @param CombateInfo $combate
	 */
	protected function validar(&$combate) {
		if (!($combate->getIdPersonagem() > 0)) {
			throw new Exception('Informe o personagem principal.');
		}
		if (!($combate->getCodSituacao() > 0)) {
			$combate->setCodSituacao(CombateInfo::SITUACAO_EM_COMBATE);
		}
		if (count($combate->listarPersonagem()) < 2) {
            throw new Exception('O combate precisa de pelomenos 2 indivíduos.');
        }
	}

	/**
	 * @throws Exception
	 * @param CombateInfo $combate
     * @return int
	 */
	public function inserir($combate) {
	    $id_combate = 0;
		$this->validar($combate);
		$dal = new CombateDAL();
		$dalPersonagem = new CombatePersonagemDAL();
		$dalMembro = new CombateMembroDAL();
		try{
		    DB::beginTransaction();
			$id_combate = $dal->inserir($combate);
			//throw new Exception($id_combate);
            foreach ($combate->listarPersonagem() as $personagem) {
                $personagem->setIdCombate($id_combate);
                $dalPersonagem->inserir($personagem);
                $armas = $personagem->getPersonagem()->listarArma();
                foreach ($armas as $arma) {
                    $membro = new CombateMembroInfo();
                    $membro->setIdCombate($id_combate);
                    $membro->setIdPersonagem($personagem->getIdPersonagem());
                    $membro->setDanoGerado(0);
                    $membro->setCodSituacao(CombateMembroInfo::SITUACAO_NORMAL);
                    $membro->setLocal($arma->getLocal());
                    $membro->setItemSlug($arma->getItemSlug());
                    $dalMembro->inserir($membro);
                }
            }
            DB::commit();
		}
		catch (Exception $e){
		    DB::rollBack();
			throw $e;
		}
		return $id_combate;
	}

	/**
	 * @throws Exception
	 * @param CombateInfo $combate
	 */
	public function alterar($combate) {
		$this->validar($combate);
		$dal = new CombateDAL();
		$dalPersonagem = new CombatePersonagemDAL();
        $dalMembro = new CombateMembroDAL();
        $dalObjeto = new CombateObjetoChaoDAL();
        $dalLog = new CombateLogDAL();
		try{
		    DB::beginTransaction();
			$dal->alterar($combate);
			// Atualizando os objetos com a base de dados
            foreach ($combate->listarPersonagem() as $personagem) {
                $personagem->listarMembro();
                $personagem->listarObjetoChao();
            }
            $dalMembro->limpar($combate->getId());
            $dalPersonagem->limpar($combate->getId());
            $dalObjeto->limpar($combate->getId());

            foreach ($combate->listarPersonagem() as $personagem) {
                $personagem->setIdCombate($combate->getId());
                $dalPersonagem->inserir($personagem);
                foreach ($personagem->listarMembro() as $membro) {
                    if (!isNullOrEmpty($membro->getItemSlug())) {
                        $membro->setIdCombate($combate->getId());
                        $membro->setIdPersonagem($personagem->getIdPersonagem());
                        $dalMembro->inserir($membro);
                    }
                }
                foreach ($personagem->listarObjetoChao() as $itemSlug => $quantidade) {
                    if ($quantidade > 0) {
                        $objeto = new CombateObjetoChaoInfo();
                        $objeto->setIdCombate($combate->getId());
                        $objeto->setIdPersonagem($personagem->getIdPersonagem());
                        $objeto->setItemSlug($itemSlug);
                        $objeto->setQuantidade($quantidade);
                        $dalObjeto->inserir($objeto);
                    }
                }
            }
            foreach ($combate->listarLog() as $log) {
                $log->setIdCombate($combate->getId());
                $dalLog->inserir($log);
            }
            $combate->limparLog();
			DB::commit();
		}
		catch (Exception $e){
		    DB::rollBack();
			throw $e;
		}
	}

	/**
	 * @throws Exception
	 * @param int $id_combate
	 */
	public function excluir($id_combate) {
        $dal = new CombateDAL();
        $dalPersonagem = new CombatePersonagemDAL();
        $dalMembro = new CombateMembroDAL();
		try{
		    DB::beginTransaction();
            $dalMembro->limpar($id_combate);
            $dalPersonagem->limpar($id_combate);
            $dal->excluir($id_combate);
			DB::commit();
		}
		catch (Exception $e){
		    DB::rollBack();
			throw $e;
		}
	}

    /**
     * @param int $id_combate
     * @return CombateLogInfo[]
     * @throws \Landim32\EasyDB\DBException
     */
	public function listarLog($id_combate) {
        $dalLog = new CombateLogDAL();
        return $dalLog->listar($id_combate);
    }


    /*
	public function adicionarLog($combate, $codTipo, $mensagem) {
        $log = new CombateLogInfo();
        $log->setIdCombate($combate->getId());
        $log->setIdPersonagem($combate->getIdPersonagem());
        $log->setCodTipo($codTipo);
        $log->setMensagem($mensagem);

        $dalLog = new CombateLogDAL();
        $dalLog->inserir($log);
    }
    */

    /**
     * @param int $id_personagem
     * @return CombatePersonagemInfo
     */
	public function gerarPersonagem($id_personagem) {
        $personagem = new CombatePersonagemInfo();
        $personagem->setIdPersonagem($id_personagem);
        $personagem->setVidaGasta(0);
        $personagem->setFadigaGasta(0);
        $personagem->setCodPosicao(CombatePersonagemInfo::POSICAO_EM_PE);
        $personagem->setCodSituacao(CombatePersonagemInfo::SITUACAO_ACORDADO);
        $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_LIVRE);
        $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_LIVRE);
        $personagem->setPernas(CombatePersonagemInfo::MEMBRO_LIVRE);
        return $personagem;
    }

    /**
     * @param int $id_personagem
     * @param int $inimigos
     * @throws Exception
     * @return int
     */
	public function gerar($id_personagem, $inimigos = 1) {
	    $regraPersonagem = new PersonagemBLL();
	    $personagem = $regraPersonagem->pegar($id_personagem);
	    if ($personagem->getTurno() < 10) {
	        throw new Exception("Você não tem turnos suficientes.");
        }
        $personagem->setBaseTurno($personagem->getBaseTurno() - 10);
	    $regraPersonagem->alterar($personagem);

	    $combate = new CombateInfo();
	    $combate->setIdPersonagem($id_personagem);
	    $combate->setCodSituacao(CombateInfo::SITUACAO_EM_COMBATE);

	    $combate->adicionarPersonagem($this->gerarPersonagem($id_personagem));
        $individuos = $regraPersonagem->listarInimigo($id_personagem, $inimigos);
        foreach ($individuos as $individuo) {
            $combate->adicionarPersonagem($this->gerarPersonagem($individuo->getId()));
        }

	    return $this->inserir($combate);
    }

    /**
     * @param string $posicao
     * @param int $bonusPe
     * @param int $bonusAgachado
     * @param int $bonusCaido
     * @return int
     */
    private function pegarBonusPorPosicao($posicao, $bonusPe = 0, $bonusAgachado = -2, $bonusCaido = -3) {
        $bonus = 0;
        switch ($posicao) {
            case CombatePersonagemInfo::POSICAO_AGACHADO:
                $bonus += $bonusAgachado;
                break;
            case CombatePersonagemInfo::POSICAO_CAIDO:
                $bonus += $bonusCaido;
                break;
            default:
                $bonus += $bonusPe;
                break;
        }
        return $bonus;
    }

    /**
     * @param int $nh
     * @param string $posicao
     * @return AcaoLocalInfo[]
     */
    private function listarAtaqueLocal($nh, $posicao) {
	    $locais = array();
	    //Cabeça
	    if ($posicao == CombatePersonagemInfo::POSICAO_EM_PE && ($nh - 7) >= 3) {
	        $local = new AcaoLocalInfo();
	        $local->setLocal(ItemInfo::CABECA);
	        $local->setNH($nh - 7);
	        $local->setDescricao("Cabeça(-7)");
	        $locais[$local->getLocal()] = $local;
        }

        //Tronco
        $bonus = $this->pegarBonusPorPosicao($posicao);
	    if (($nh + $bonus) >= 3) {
            $local = new AcaoLocalInfo();
            $local->setLocal(ItemInfo::TRONCO);
            $local->setNH($nh + $bonus);
            $local->setDescricao("Tronco(" . $bonus . ")");
            $locais[$local->getLocal()] = $local;
        }

        //Braço Direito
        $bonus = $this->pegarBonusPorPosicao($posicao, -2, -4, -5);
        if (($nh + $bonus) >= 3) {
            $local = new AcaoLocalInfo();
            $local->setLocal(ItemInfo::BRACO_DIREITO);
            $local->setNH($nh + $bonus);
            $local->setDescricao("Braço Direito(" . $bonus . ")");
            $locais[$local->getLocal()] = $local;
        }

        //Braço Esquerdo
        $bonus = $this->pegarBonusPorPosicao($posicao, -2, -4, -5);
        if (($nh + $bonus) >= 3) {
            $local = new AcaoLocalInfo();
            $local->setLocal(ItemInfo::BRACO_ESQUERDO);
            $local->setNH($nh + $bonus);
            $local->setDescricao("Braço Esquerdo(" . $bonus . ")");
            $locais[$local->getLocal()] = $local;
        }

        //Mão Direita
        $bonus = $this->pegarBonusPorPosicao($posicao, -3, -5, -6);
        if (($nh + $bonus) >= 3) {
            $local = new AcaoLocalInfo();
            $local->setLocal(ItemInfo::MAO_DIREITA);
            $local->setNH($nh + $bonus);
            $local->setDescricao("Mão Direita(" . $bonus . ")");
            $locais[$local->getLocal()] = $local;
        }

        //Mão Esquerda
        $bonus = $this->pegarBonusPorPosicao($posicao, -3, -5, -6);
        if (($nh + $bonus) >= 3) {
            $local = new AcaoLocalInfo();
            $local->setLocal(ItemInfo::MAO_ESQUERDA);
            $local->setNH($nh + $bonus);
            $local->setDescricao("Mão Esquerda(" . $bonus . ")");
            $locais[$local->getLocal()] = $local;
        }

        //Perna Direito
        $bonus = $this->pegarBonusPorPosicao($posicao, -2, 0, -1);
        if (($nh + $bonus) >= 3) {
            $local = new AcaoLocalInfo();
            $local->setLocal(ItemInfo::PERNA_DIREITA);
            $local->setNH($nh + $bonus);
            $local->setDescricao("Perna Direita(" . $bonus . ")");
            $locais[$local->getLocal()] = $local;
        }

        //Perna Esquerda
        $bonus = $this->pegarBonusPorPosicao($posicao, -2, 0, -1);
        if (($nh + $bonus) >= 3) {
            $local = new AcaoLocalInfo();
            $local->setLocal(ItemInfo::PERNA_ESQUERDA);
            $local->setNH($nh + $bonus);
            $local->setDescricao("Perna Esquerda(" . $bonus . ")");
            $locais[$local->getLocal()] = $local;
        }

        return $locais;
    }

    /**
     * @return array
     */
    private function tabelaDanoPorForca() {
        return array(
             1 => ['gdp' => [ 0, 0], 'bal' => [ 0, 0]],
             2 => ['gdp' => [ 0, 0], 'bal' => [ 0, 0]],
             3 => ['gdp' => [ 0, 0], 'bal' => [ 0, 0]],
             4 => ['gdp' => [ 0, 0], 'bal' => [ 0, 0]],
             5 => ['gdp' => [ 1,-5], 'bal' => [ 1,-5]],
             6 => ['gdp' => [ 1,-4], 'bal' => [ 1,-4]],
             7 => ['gdp' => [ 1,-3], 'bal' => [ 1,-3]],
             8 => ['gdp' => [ 1,-3], 'bal' => [ 1,-2]],
             9 => ['gdp' => [ 1,-2], 'bal' => [ 1,-1]],
            10 => ['gdp' => [ 1,-2], 'bal' => [ 1, 0]],
            11 => ['gdp' => [ 1,-2], 'bal' => [ 1, 1]],
            12 => ['gdp' => [ 1,-1], 'bal' => [ 1, 2]],
            13 => ['gdp' => [ 1, 0], 'bal' => [ 2,-1]],
            14 => ['gdp' => [ 1, 0], 'bal' => [ 2, 0]],
            15 => ['gdp' => [ 1, 1], 'bal' => [ 2, 1]],
            16 => ['gdp' => [ 1, 1], 'bal' => [ 2, 2]],
            17 => ['gdp' => [ 1, 2], 'bal' => [ 3,-1]],
            18 => ['gdp' => [ 1, 2], 'bal' => [ 3, 0]],
            19 => ['gdp' => [ 2,-1], 'bal' => [ 3, 1]],
            20 => ['gdp' => [ 2,-1], 'bal' => [ 3, 2]],
        );
    }

    /**
     * @param PersonagemInfo $personagem
     * @param string $tipoDano
     * @param int $bonus
     * @return DanoInfo
     */
    public function gerarDano($personagem, $tipoDano, $bonus) {
        $danoPorForca = $this->tabelaDanoPorForca();
        $danoVetor = $danoPorForca[$personagem->getForca()][$tipoDano];
        return new DanoInfo($danoVetor[0], $danoVetor[1] + $bonus);
    }

    /**
     * @throws Exception
     * @param CombatePersonagemInfo $personagem
     * @param ItemInfo|null $item
     * @param string $local
     * @return AcaoInfo[]
     */
    private function listarAcaoPorItem($personagem, $item, $local) {
        $coldres = array(
            ItemInfo::TRONCO
        );
        if (is_null($item) || $item->getTipo() == ItemInfo::ARMA) {
            $coldres[] = ItemInfo::PERNA_DIREITA;
            $coldres[] = ItemInfo::PERNA_ESQUERDA;
        }
        $posicoes = array(
            CombatePersonagemInfo::POSICAO_AGACHADO,
            CombatePersonagemInfo::POSICAO_CAIDO
        );

	    $acoes = array();

	    if (!is_null($item)) {
            $armas = array(
                ItemInfo::ARMA,
                ItemInfo::ARMA_LONGE,
                ItemInfo::ARMA_2MAOS
            );
            $nh = $personagem->getPersonagem()->getNH($item->getPericiaSlug());
            if ($item->getTipo() == ItemInfo::ESCUDO) {
                $coldres = array(
                    ItemInfo::TRONCO
                );

                $acao = new AcaoInfo();
                $acao->setSlug($item->getSlug());
                $acao->setNome("Escudada");
                if ($local == ItemInfo::MAO_DIREITA) {
                    $acao->setGrupo(AcaoInfo::GRUPO_MAO_DIREITA);
                } elseif ($local == ItemInfo::MAO_ESQUERDA) {
                    $acao->setGrupo(AcaoInfo::GRUPO_MAO_ESQUERDA);
                }
                $acao->setDescricao($item->getNomeCurto());
                $acao->setTipo(ItemAtributoInfo::CONTUSAO);
                $acao->setItemSlug($item->getSlug());
                $acao->setLocal($local);
                $acao->setNH($nh);
                $acao->setDano($this->gerarDano($personagem->getPersonagem(), ItemAtributoInfo::CONTUSAO, -2));
                //$acao->setPontoImpacto($this->listarAtaqueLocal($nh, $personagem->getCodPosicao()));
                $acao->setAtivo($personagem->podeUsarMembro($local));

                $pontoImpacto = new AcaoLocalInfo();
                $pontoImpacto->setLocal(ItemInfo::TRONCO);
                $pontoImpacto->setNH($nh);
                $pontoImpacto->setDescricao("Tronco(0)");
                $acao->adicionarPontoImpacto($pontoImpacto);

                $acoes[] = $acao;
            }
            elseif (in_array($item->getTipo(), $armas)) {
                foreach ($item->listarAtributo() as $atributo) {
                    if ($local == ItemInfo::MAO_ESQUERDA) {
                        $nh -= 4;
                    }
                    if ($atributo->getSlug() == ItemAtributoInfo::CORTE) {
                        $tipoDano = 'bal';
                    } else {
                        $tipoDano = 'gdp';
                    }

                    $acao = new AcaoInfo();
                    $acao->setSlug($item->getSlug());
                    $acao->setNome($atributo->getNome());
                    if ($local == ItemInfo::MAO_DIREITA) {
                        $acao->setGrupo(AcaoInfo::GRUPO_MAO_DIREITA);
                    } elseif ($local == ItemInfo::MAO_ESQUERDA) {
                        $acao->setGrupo(AcaoInfo::GRUPO_MAO_ESQUERDA);
                    }
                    $acao->setDescricao($item->getNomeCurto());
                    $acao->setTipo($atributo->getSlug());
                    $acao->setItemSlug($item->getSlug());
                    $acao->setLocal($local);
                    $acao->setNH($nh);
                    $acao->setDano($this->gerarDano($personagem->getPersonagem(), $tipoDano, $atributo->getValor()));
                    $acao->setPontoImpacto($this->listarAtaqueLocal($nh, $personagem->getCodPosicao()));
                    $acao->setAtivo($personagem->podeUsarMembro($local));
                    $acoes[] = $acao;
                }
            }

            $acao = new AcaoInfo();
            $acao->setSlug(AcaoInfo::SOLTAR_ARMA);
            $acao->setNome(sprintf("Soltar %s no chão", $item->getNomeCurto()));
            $acao->setItemSlug($item->getSlug());
            $acao->setLocal($local);
            if ($local == ItemInfo::MAO_DIREITA) {
                $acao->setGrupo( AcaoInfo::GRUPO_MAO_DIREITA);
            }
            elseif ($local == ItemInfo::MAO_ESQUERDA) {
                $acao->setGrupo( AcaoInfo::GRUPO_MAO_ESQUERDA);
            }
            $acao->setDescricao("Solta a arma no chão. Isso não é considerado uma ação.");
            $acao->setAtivo($personagem->podeUsarMembro($local));
            $acoes[] = $acao;

            foreach ($coldres as $coldre) {
                $membro = $personagem->getMembro($coldre);
                if (is_null($membro->getItem())) {
                    $coldreStr = "";
                    switch ($coldre) {
                        case ItemInfo::TRONCO:
                            $coldreStr = " nas Costas";
                            break;
                        case ItemInfo::PERNA_DIREITA:
                            $coldreStr = " na perna direita";
                            break;
                        case ItemInfo::PERNA_ESQUERDA:
                            $coldreStr = " na perna esquerda";
                            break;
                    }

                    $acao = new AcaoInfo();
                    $acao->setSlug(AcaoInfo::GUARDAR_ARMA);
                    $acao->setNome(sprintf("Guardar %s %s", $item->getNomeCurto(), $coldreStr));
                    $acao->setItemSlug($item->getSlug());
                    $acao->setLocal($coldre);
                    if ($local == ItemInfo::MAO_DIREITA) {
                        $acao->setGrupo( AcaoInfo::GRUPO_MAO_DIREITA);
                    }
                    elseif ($local == ItemInfo::MAO_ESQUERDA) {
                        $acao->setGrupo( AcaoInfo::GRUPO_MAO_ESQUERDA);
                    }
                    $acao->setDescricao("Guarda a arma no coldre. Isso gasta uma ação com essa mão.");
                    $acao->setAtivo($personagem->podeUsarMembro($local));
                    $acoes[] = $acao;
                }
            }
        }
        else {
            $nh = $personagem->getPersonagem()->getNH(PericiaInfo::PERICIA_BRIGA);
            if ($nh >= 3) {
                $acao = new AcaoInfo();
                $acao->setSlug(AcaoInfo::SOCO);
                $acao->setNome("Soco");
                if ($local == ItemInfo::MAO_DIREITA) {
                    $acao->setGrupo( AcaoInfo::GRUPO_MAO_DIREITA);
                }
                elseif ($local == ItemInfo::MAO_ESQUERDA) {
                    $acao->setGrupo( AcaoInfo::GRUPO_MAO_ESQUERDA);
                }
                $acao->setDescricao("Dê um soco em seu inimigo com a mão livre.");
                $acao->setTipo(ItemAtributoInfo::CONTUSAO);
                $acao->setLocal($local);
                $acao->setNH($nh);
                $acao->setDano($this->gerarDano($personagem->getPersonagem(), 'gdp', -2));
                //$acao->setLocal($this->listarAtaqueLocal($nh, $personagem->getCodPosicao()));
                $acao->setAtivo($personagem->podeUsarMembro($local));

                if ($personagem->getCodPosicao() == CombatePersonagemInfo::POSICAO_EM_PE && ($nh - 5) >= 3) {
                    $pontoImpacto = new AcaoLocalInfo();
                    $pontoImpacto->setLocal(ItemInfo::CABECA);
                    $pontoImpacto->setNH($nh - 5);
                    $pontoImpacto->setDescricao("Rosto(-5)");
                    $acao->adicionarPontoImpacto($pontoImpacto);
                }

                //Tronco
                $bonus = $this->pegarBonusPorPosicao($personagem->getCodPosicao());
                if (($nh + $bonus) >= 3) {
                    $pontoImpacto = new AcaoLocalInfo();
                    $pontoImpacto->setLocal(ItemInfo::TRONCO);
                    $pontoImpacto->setNH($nh + $bonus);
                    $pontoImpacto->setDescricao("Tronco(" . $bonus . ")");
                    $acao->adicionarPontoImpacto($pontoImpacto);
                }

                if (count($acao->listarPontoImpacto()) > 0) {
                    $acoes[] = $acao;
                }
            }

            foreach ($coldres as $coldre) {
                $arma = $personagem->getMembro($coldre)->getItem();
                if (!is_null($arma)) {
                    if (!($arma->getTipo() == ItemInfo::ARMA_2MAOS && $local == ItemInfo::MAO_ESQUERDA)) {
                        $acao = new AcaoInfo();
                        $acao->setSlug(AcaoInfo::PEGAR_ARMA_COLDRI);
                        $acao->setNome("Sacar " . $arma->getNome());
                        $acao->setItemSlug($arma->getSlug());
                        $acao->setLocal($local);
                        if ($local == ItemInfo::MAO_DIREITA) {
                            $acao->setGrupo(AcaoInfo::GRUPO_MAO_DIREITA);
                        } elseif ($local == ItemInfo::MAO_ESQUERDA) {
                            $acao->setGrupo(AcaoInfo::GRUPO_MAO_ESQUERDA);
                        }
                        $acao->setDescricao("Pegue a arma no coldri.");
                        $acao->setAtivo($personagem->podeUsarMembro($local));
                        $acoes[] = $acao;
                    }
                }
            }
            if (in_array($personagem->getCodPosicao(), $posicoes)) {
                foreach ($personagem->listarItemChao() as $objeto) {
                    $arma = $objeto->getItem();
                    if (!($arma->getTipo() == ItemInfo::ARMA_2MAOS && $local == ItemInfo::MAO_ESQUERDA)) {
                        $acao = new AcaoInfo();
                        $acao->setSlug(AcaoInfo::PEGAR_ARMA_CHAO);
                        $acao->setNome(sprintf("Pegar %s no chão", $arma->getNome()));
                        $acao->setItemSlug($objeto->getItemSlug());
                        $acao->setLocal($local);
                        if ($local == ItemInfo::MAO_DIREITA) {
                            $acao->setGrupo(AcaoInfo::GRUPO_MAO_DIREITA);
                        } elseif ($local == ItemInfo::MAO_ESQUERDA) {
                            $acao->setGrupo(AcaoInfo::GRUPO_MAO_ESQUERDA);
                        }
                        $acao->setDescricao("Pegue a arma que caiu no chão. Você terá que levantar depois.");
                        $acao->setAtivo($personagem->podeUsarMembro($local));
                        $acoes[] = $acao;
                    }
                }
            }
        }

	    return $acoes;
    }

    /**
     * @throws Exception
     * @param CombatePersonagemInfo $personagem
     * @return AcaoInfo[]
     */
    public function listarAcaoPosicao($personagem) {
        $acoes = array();
        if ($personagem->getCodPosicao() == CombatePersonagemInfo::POSICAO_EM_PE) {
            $acao = new AcaoInfo();
            $acao->setSlug(AcaoInfo::POSICAO_AGACHAR);
            $acao->setNome("Agachar");
            $acao->setGrupo(AcaoInfo::GRUPO_POSICAO);
            $acao->setDescricao("Agachar para se protejer ou pegar algo no chão.");
            $acao->setAtivo($personagem->podeFicarEmPe() && !$personagem->executouAlgumaAcao());
            $acoes[] = $acao;

            $acao = new AcaoInfo();
            $acao->setSlug(AcaoInfo::POSICAO_DEITAR);
            $acao->setNome("Se jogar");
            $acao->setGrupo(AcaoInfo::GRUPO_POSICAO);
            $acao->setDescricao("Se jogar no chão para se proteger.");
            $acao->setAtivo(!$personagem->executouAlgumaAcao());
            $acoes[] = $acao;
        }
        if ($personagem->getCodPosicao() == CombatePersonagemInfo::POSICAO_AGACHADO) {
            $acao = new AcaoInfo();
            $acao->setSlug(AcaoInfo::POSICAO_LEVANTAR);
            $acao->setNome("Levantar");
            $acao->setGrupo(AcaoInfo::GRUPO_POSICAO);
            $acao->setDescricao("Levantar para enfrentar seus inimigos.");
            $acao->setAtivo($personagem->podeFicarEmPe() && !$personagem->executouAlgumaAcao());
            $acoes[] = $acao;

            $acao = new AcaoInfo();
            $acao->setSlug(AcaoInfo::POSICAO_DEITAR);
            $acao->setNome("Deitar");
            $acao->setGrupo(AcaoInfo::GRUPO_POSICAO);
            $acao->setDescricao("Deitar para se protejer.");
            $acao->setAtivo(!$personagem->executouAlgumaAcao());
            $acoes[] = $acao;
        }
        if ($personagem->getCodPosicao() == CombatePersonagemInfo::POSICAO_CAIDO) {
            $acao = new AcaoInfo();
            $acao->setSlug(AcaoInfo::POSICAO_AGACHAR);
            $acao->setNome("Levantar");
            $acao->setGrupo(AcaoInfo::GRUPO_POSICAO);
            $acao->setDescricao("Começar a se levantar. Você passará para a posição agachado.");
            $acao->setAtivo($personagem->podeFicarEmPe() && !$personagem->executouAlgumaAcao());
            $acoes[] = $acao;
        }
        return $acoes;
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param int $id_personagem
     * @return AcaoInfo[]
     */
    public function listarAcao($combate, $id_personagem) {
        $acoes = array();
        if ($combate->getCodSituacao() != CombateInfo::SITUACAO_EM_COMBATE) {
            return $acoes;
        }
        $personagem = $combate->getPersonagem($id_personagem);

        $acao = new AcaoInfo();
        $acao->setSlug(AcaoInfo::EVADIR);
        $acao->setNome("Evadir");
        $acao->setGrupo(AcaoInfo::GRUPO_MOVIMENTO);
        $acao->setDescricao("Aumenta a chance de ser defender, mas não pode atacar nesee turno.");
        $acao->setAtivo($personagem->podeEvadir());
        $acoes[] = $acao;

        $maoDireita = $personagem->getMembro(ItemInfo::MAO_DIREITA)->getItem();
        $acaoDireita = $this->listarAcaoPorItem($personagem, $maoDireita, ItemInfo::MAO_DIREITA);
        foreach ($acaoDireita as $acao) {
            $acoes[] = $acao;
        }

        if (!(!is_null($maoDireita) && $maoDireita->getTipo() == ItemInfo::ARMA_2MAOS)) {
            $maoEsquerda = $personagem->getMembro(ItemInfo::MAO_ESQUERDA)->getItem();
            $acaoEsquerda = $this->listarAcaoPorItem($personagem, $maoEsquerda, ItemInfo::MAO_ESQUERDA);
            foreach ($acaoEsquerda as $acao) {
                $acoes[] = $acao;
            }
        }

        $nh = $personagem->getPersonagem()->getNH(PericiaInfo::PERICIA_BRIGA);
        if ($nh >= 3) {
            $acao = new AcaoInfo();
            $acao->setSlug(AcaoInfo::CHUTE);
            $acao->setNome("Chute");
            $acao->setGrupo(AcaoInfo::GRUPO_CHUTE);
            $acao->setDescricao("Dê um chute em seu inimigo. Mas você não poderá atacar com as mãos nesse turno.");
            $acao->setTipo(ItemAtributoInfo::CONTUSAO);
            $acao->setNH($nh);
            $acao->setDano($this->gerarDano($personagem->getPersonagem(), 'gdp', -1));
            $acao->setPontoImpacto($this->listarAtaqueLocal($nh, $personagem->getCodPosicao()));
            $acao->setAtivo($personagem->podeFicarEmPe() && !$personagem->executouAlgumaAcao());
            $acoes[] = $acao;
        }

        $acaoPosicao = $this->listarAcaoPosicao($personagem);
        foreach ($acaoPosicao as $acao) {
            $acoes[] = $acao;
        }

        $acao = new AcaoInfo();
        $acao->setSlug(AcaoInfo::TURNO);
        $acao->setNome("Terminar Turno");
        $acao->setGrupo(AcaoInfo::GRUPO_TURNO);
        $acao->setDescricao("Finaliza o turno. Os inimigos executarão seus movimentos.");
        $acao->setAtivo(true);
        $acoes[] = $acao;

	    return $acoes;
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param AcaoInfo $acao
     * @return bool
     */
    private function executarAcaoPosicao($combate, $personagem, $acao) {
        $retorno = false;

        if (!$acao->getAtivo()) {
            $formato = "%s não pode executar a ação '%s'.";
            $mensagem = sprintf($formato, $personagem->getPersonagem()->getNome(), $acao->getNome());
            throw new Exception($mensagem);
        }

        switch ($acao->getSlug()) {
            case AcaoInfo::POSICAO_LEVANTAR:
                if ($personagem->getCodPosicao() != CombatePersonagemInfo::POSICAO_AGACHADO) {
                    throw new Exception("Vocë não está agachado para poder se levantar.");
                }
                if ($personagem->getCodPosicao() == CombatePersonagemInfo::POSICAO_EM_PE) {
                    throw new Exception("Vocë já está em pé.");
                }
                $personagem->setCodPosicao(CombatePersonagemInfo::POSICAO_EM_PE);
                $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_USADA);
                $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_USADA);
                $personagem->setPernas(CombatePersonagemInfo::MEMBRO_USADA);

                //$this->alterar($combate);
                $mensagem = sprintf("%s está em pé.", $personagem->getPersonagem()->getNome());
                $combate->adicionarLog($mensagem, CombateLogInfo::POSICAO, $personagem->getIdPersonagem());

                break;
            case AcaoInfo::POSICAO_AGACHAR:
                if ($personagem->getCodPosicao() == CombatePersonagemInfo::POSICAO_AGACHADO) {
                    throw new Exception("Vocë já está agachado.");
                }
                $personagem->setCodPosicao(CombatePersonagemInfo::POSICAO_AGACHADO);
                $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_USADA);
                $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_USADA);
                $personagem->setPernas(CombatePersonagemInfo::MEMBRO_USADA);

                //$this->alterar($combate);
                $mensagem = sprintf("%s está agachado.", $personagem->getPersonagem()->getNome());
                $combate->adicionarLog($mensagem, CombateLogInfo::POSICAO, $personagem->getIdPersonagem());
                break;
            case AcaoInfo::POSICAO_DEITAR:
                if ($personagem->getCodPosicao() == CombatePersonagemInfo::POSICAO_CAIDO) {
                    throw new Exception("Vocë já está no chão.");
                }
                $personagem->setCodPosicao(CombatePersonagemInfo::POSICAO_CAIDO);
                $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_USADA);
                $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_USADA);
                $personagem->setPernas(CombatePersonagemInfo::MEMBRO_USADA);

                //$this->alterar($combate);
                $mensagem = sprintf("%s está no chão.", $personagem->getPersonagem()->getNome());
                $combate->adicionarLog($mensagem, CombateLogInfo::POSICAO, $personagem->getIdPersonagem());
                break;
            default:
                throw new Exception(sprintf("A ação %s não foi encontrada!", $acao->getNome()));
                break;
        }
        return $retorno;
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param AcaoInfo $acao
     * @param string|null $itemSlug
     * @param string|null $local
     * @return bool
     */
    private function executarAcaoInterna($combate, $personagem, $acao, $itemSlug = null, $local = null) {
        $retorno = false;
        switch ($acao->getSlug()) {
            case AcaoInfo::POSICAO_LEVANTAR:
                $retorno = $this->executarAcaoPosicao($combate, $personagem, $acao);
                break;
            case AcaoInfo::POSICAO_AGACHAR:
                $retorno = $this->executarAcaoPosicao($combate, $personagem, $acao);
                break;
            case AcaoInfo::POSICAO_DEITAR:
                $retorno = $this->executarAcaoPosicao($combate, $personagem, $acao);
                break;
            case AcaoInfo::SOLTAR_ARMA:
                $retorno = $this->soltarArma($combate, $personagem, $local);
                break;
            case AcaoInfo::GUARDAR_ARMA:
                $retorno = $this->guardarArma($combate, $personagem, $itemSlug, $local);
                break;
            case AcaoInfo::PEGAR_ARMA_COLDRI:
                $retorno = $this->sacarArma($combate, $personagem, $itemSlug, $local);
                break;
            case AcaoInfo::PEGAR_ARMA_CHAO:
                $retorno = $this->pegarDoChao($combate, $personagem, $itemSlug, $local);
                break;
            case AcaoInfo::TURNO:
                $retorno = $this->finalizarTurno($combate);
                $this->alterar($combate);
                break;
            default:
                throw new Exception(sprintf("A ação %s não foi encontrada!", $acao->getNome()));
                break;
        }
        return $retorno;
    }

    /**
     * @param int $id_combate
     * @param string $acaoSlug Ação a ser executado (slug)
     * @param string|null $itemSlug
     * @param string|null $local
     * @return bool
     * @throws Exception
     */
    public function executarAcao($id_combate, $acaoSlug, $itemSlug = null, $local = null) {
        $combate = $this->pegar($id_combate);

        $personagem = $combate->getPersonagem($combate->getIdPersonagem());
        $acoes = $this->listarAcao($combate, $combate->getIdPersonagem());
        $acao = null;
        foreach ($acoes as $a) {
            if ($a->getSlug() == $acaoSlug) {
                $acao = $a;
                break;
            }
        }
        if (is_null($personagem)) {
            throw new Exception(sprintf("Nenhum personagem encontrado com o id %s.", $combate->getIdPersonagem()));
        }
        if (is_null($acao)) {
            throw new Exception(sprintf("Nenhuma ação encontrada com o slug %s.", $acaoSlug));
        }
        $retorno = $this->executarAcaoInterna($combate, $personagem, $acao, $itemSlug, $local);
        $this->validarCombate($combate);
        $this->alterar($combate);
        if ($combate->getCodSituacao() == CombateInfo::SITUACAO_EM_COMBATE) {
            $this->verificarTurno($id_combate);
        }
        return $retorno;
    }

    /**
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param string $local
     * @return bool
     * @throws Exception
     */
    private function executarEsquiva($combate, $personagem, $local) {
        $esquivou = false;
        $esquiva = $personagem->getEsquiva($local);
        if ($esquiva > 3) {
            $jogada = DadoBLL::jogar();
            if ($jogada <= $esquiva) {
                $formato = "%s conseguiu se esquivar do ataque (%s/%s).";
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $jogada,
                    $esquiva
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::DEFESA_SUCESSO, $personagem->getIdPersonagem());
                $esquivou = true;
            }
            elseif ($jogada >= 17) {
                $formato = "%s sofreu uma falha crítica ao tentar se esquivar do ataque (%s/%s).";
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $jogada,
                    $esquiva
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::DEFESA_FALHA_CRITICA, $personagem->getIdPersonagem());
            }
            else {
                $formato = "%s não conseguiu se esquivar do ataque (%s/%s).";
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $jogada,
                    $esquiva
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::DEFESA_FALHA, $personagem->getIdPersonagem());
            }
        }
        return $esquivou;
    }

    /**
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @return bool
     * @throws Exception
     */
    private function executarBloqueio($combate, $personagem) {
        $bloqueou = false;
        if ($personagem->getBloqueio() > 3) {
            $jogada = DadoBLL::jogar();
            if ($jogada <= $personagem->getBloqueio()) {
                $formato = "%s conseguiu bloquear do ataque (%s/%s).";
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $jogada,
                    $personagem->getBloqueio()
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::DEFESA_SUCESSO, $personagem->getIdPersonagem());
                $bloqueou = true;
            }
            elseif ($jogada >= 17) {
                $formato = "%s sofreu uma falha crítica ao tentar bloquear o ataque (%s/%s).";
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $jogada,
                    $personagem->getBloqueio()
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::DEFESA_FALHA_CRITICA, $personagem->getIdPersonagem());
            }
            else {
                $formato = "%s não conseguiu bloquear o ataque (%s/%s).";
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $jogada,
                    $personagem->getBloqueio()
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::DEFESA_FALHA, $personagem->getIdPersonagem());
            }
        }
        return $bloqueou;
    }

    /**
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param string $mao
     * @return bool
     * @throws Exception
     */
    private function executarAparar($combate, $personagem, $mao) {
        $aparou = false;
        if ($personagem->getAparar($mao) > 3) {
            $jogada = DadoBLL::jogar();
            if ($jogada <= $personagem->getAparar($mao)) {
                $formato = "%s conseguiu aparar o ataque (%s/%s).";
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $jogada,
                    $personagem->getAparar($mao)
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::DEFESA_SUCESSO, $personagem->getIdPersonagem());
                $aparou = true;
            }
            elseif ($jogada >= 17) {
                $formato = "%s sofreu uma falha crítica ao tentar aparar o ataque (%s/%s).";
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $jogada,
                    $personagem->getAparar($mao)
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::DEFESA_FALHA_CRITICA, $personagem->getIdPersonagem());
            }
            else {
                $formato = "%s não conseguiu aparar o ataque (%s/%s).";
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $jogada,
                    $personagem->getAparar($mao)
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::DEFESA_FALHA, $personagem->getIdPersonagem());
            }
        }
        return $aparou;
    }

    /**
     * @param CombatePersonagemInfo $personagem
     */
    private function morto($personagem) {
        $personagem->setCodSituacao(CombatePersonagemInfo::SITUACAO_MORTO);
        $personagem->setCodPosicao(CombatePersonagemInfo::POSICAO_CAIDO);
        $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_DEIXOU_ARMA_CAIR);
        $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_DEIXOU_ARMA_CAIR);
    }

    /**
     * @param CombatePersonagemInfo $personagem
     */
    private function nocautiado($personagem) {
        $personagem->setCodSituacao(CombatePersonagemInfo::SITUACAO_DESMAIADO);
        $personagem->setCodPosicao(CombatePersonagemInfo::POSICAO_CAIDO);
        $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_DEIXOU_ARMA_CAIR);
        $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_DEIXOU_ARMA_CAIR);
    }

    /**
     * @param CombatePersonagemInfo $personagem
     */
    private function prostrado($personagem) {
        $personagem->setCodSituacao(CombatePersonagemInfo::SITUACAO_ATORDOADO);
        $personagem->setCodPosicao(CombatePersonagemInfo::POSICAO_CAIDO);
        $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_DEIXOU_ARMA_CAIR);
        $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_DEIXOU_ARMA_CAIR);
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @return bool
     */
    private function verificarVida($combate, $personagem) {
        $vida = $personagem->getVida();
        if ($vida <= 0) {
            $redutor = ceil(abs($vida) / 5);
            $jogada = DadoBLL::jogar();
            $vigorAtual = $personagem->getPersonagem()->getVigor() - $redutor;
            if ($jogada > $vigorAtual) {
                $this->morto($personagem);
                //$this->alterar($combate);
                $formato = "%s morreu devido aos ferimentos sofridos (%s/%s).";
                $mensagem = sprintf($formato, $personagem->getPersonagem()->getNome(), $jogada, $vigorAtual);
                $combate->adicionarLog($mensagem, CombateLogInfo::DEFESA_FALHA, $personagem->getIdPersonagem());
                return false;
            }
        }
        return true;
    }

    /**
     * @throws Exception
     * @param CombatePersonagemInfo $personagem
     * @param string $local
     * @param int $danoGerado
     */
    private function aplicarDano($personagem, $local, $danoGerado) {
        $membro = $personagem->getMembro($local);
        $membro->setDanoGerado($membro->getDanoGerado() + $danoGerado);
        $personagem->setVidaGasta($personagem->getVidaGasta() + $danoGerado);
        $personagem->setDanoTurno($personagem->getDanoTurno() + $danoGerado);
    }

    /**
     * @param int $danoBasico
     * @param string $tipo
     * @return int
     */
    private function danoGerado($danoBasico, $tipo) {
        $danoGerado = $danoBasico;
        if ($tipo == ItemAtributoInfo::PERFURACAO) {
            $danoGerado = $danoGerado * 2;
        }
        if ($tipo == ItemAtributoInfo::CORTE) {
            $danoGerado = $danoGerado * 1.5;
        }
        return floor($danoGerado);
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param AcaoInfo $acao
     * @param AcaoLocalInfo $acaoLocal
     * @param int $danoBasico
     * @return bool
     */
    private function causarDanoCerebro($combate, $personagem, $acao, $acaoLocal, $danoBasico) {
        $danoGerado = $this->danoGerado($danoBasico, $acao->getTipo());
        $danoGerado = $danoGerado * 4;
        $this->aplicarDano($personagem, $acaoLocal->getLocal(), $danoGerado);
        $vigorAtual = $personagem->getPersonagem()->getVigor();

        if ($danoGerado > ($vigorAtual / 2)) {
            $this->nocautiado($personagem);
            $formato = "%s foi nocauteado por tem sofrido %s de dano por %s no cérebro.";
            $mensagem = sprintf(
                $formato,
                $personagem->getPersonagem()->getNome(),
                $danoGerado,
                $acao->getTipoStr()
            );
            $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_SUCESSO, $personagem->getIdPersonagem());
        }
        elseif ($danoGerado > ($vigorAtual / 3)) {
            $this->prostrado($personagem);
            $formato = "%s ficou atordoado e caiu no chão por tem sofrido %s de dano por %s no cérebro.";
            $mensagem = sprintf(
                $formato,
                $personagem->getPersonagem()->getNome(),
                $danoGerado,
                $acao->getTipoStr()
            );
            $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_SUCESSO, $personagem->getIdPersonagem());
        }
        else {
            $jogada = DadoBLL::jogar();
            if ($jogada <= $vigorAtual - 10) {
                $this->prostrado($personagem);
                $formato = "%s ficou atordoado e caiu no chão por tem sofrido %s de dano por %s no cérebro.";
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $danoGerado,
                    $acao->getTipoStr()
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_SUCESSO, $personagem->getIdPersonagem());
            }
            else {
                $formato = "%s sofreu %s de dano por %s no cérebro mas conseguiu ficar de pé (%s/%s).";
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $danoGerado,
                    $acao->getTipoStr(),
                    $jogada,
                    $vigorAtual - 10
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_SUCESSO, $personagem->getIdPersonagem());
            }
        }

        return true;
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param AcaoInfo $acao
     * @param AcaoLocalInfo $acaoLocal
     * @param int $danoBasico
     * @return bool
     */
    private function causarDanoCabeca($combate, $personagem, $acao, $acaoLocal, $danoBasico) {
        $danoGerado = $this->danoGerado($danoBasico, $acao->getTipo());
        $this->aplicarDano($personagem, $acaoLocal->getLocal(), $danoGerado);

        $vigorAtual = $personagem->getPersonagem()->getVigor();
        $jogada = DadoBLL::jogar();
        if ($jogada <= $vigorAtual - 5) {
            $this->prostrado($personagem);
            $formato = "%s ficou atordoado e caiu no chão por tem sofrido %s de dano por %s na cabeça.";
            $mensagem = sprintf(
                $formato,
                $personagem->getPersonagem()->getNome(),
                $danoGerado,
                $acao->getTipoStr()
            );
            $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_SUCESSO, $personagem->getIdPersonagem());
        }
        else {
            $formato = "%s sofreu %s de dano por %s na cabeça mas conseguiu ficar de pé (%s/%s).";
            $mensagem = sprintf(
                $formato,
                $personagem->getPersonagem()->getNome(),
                $danoGerado,
                $acao->getTipoStr(),
                $jogada,
                $vigorAtual - 5
            );
            $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_SUCESSO, $personagem->getIdPersonagem());
        }
        return true;
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param AcaoInfo $acao
     * @param AcaoLocalInfo $acaoLocal
     * @param int $danoBasico
     * @param int $danoMaximo
     * @return bool
     */
    private function causarDanoMembro($combate, $personagem, $acao, $acaoLocal, $danoBasico, $danoMaximo) {
        $danoGerado = $this->danoGerado($danoBasico, $acao->getTipo());

        $membro = $personagem->getMembro($acaoLocal->getLocal());
        //$danoAtual = $membro->getDanoGerado() + $danoGerado;
        $danoAtual = $membro->getDanoGerado();
        if (($danoAtual + $danoGerado) >= $danoMaximo) {
            //$danoFinal = $danoAtual - $danoMaximo;
            $danoFinal = $danoMaximo - $danoAtual;
            $this->aplicarDano($personagem, $acaoLocal->getLocal(), $danoFinal);

            $formato = "%s sofreu %s de dano por %s no %s que ficou incapacitado (gerado=%s, atual=%s, final=%s, máximo=%s).";
            $mensagem = sprintf(
                $formato,
                $personagem->getPersonagem()->getNome(),
                $danoFinal,
                $acao->getTipoStr(),
                $acaoLocal->getLocalStr(),
                $danoGerado,
                $danoAtual,
                $danoFinal,
                $danoMaximo
            );
            $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_SUCESSO, $personagem->getIdPersonagem());

            $vigor = $personagem->getPersonagem()->getVigor() + 3;
            $jogada = DadoBLL::jogar();
            if ($jogada <= $vigor) {
                $membro->setCodSituacao(CombateMembroInfo::SITUACAO_INCAPACITADO);
                $formato = "%s sofreu %s de dano por %s no %s que ficou incapacitado (%s/%s).";
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $danoFinal,
                    $acao->getTipoStr(),
                    $acaoLocal->getLocalStr(),
                    $jogada,
                    $vigor
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_SUCESSO, $personagem->getIdPersonagem());
            }
            else {
                $membro->setCodSituacao(CombateMembroInfo::SITUACAO_DESTRUIDO);
                switch ($acao->getTipo()) {
                    case ItemAtributoInfo::PERFURACAO:
                        $formato = "%s sofreu %s de dano e seu %s ficou incapacitado permanentemente (%s/%s)!";
                        break;
                    case ItemAtributoInfo::CORTE:
                        $formato = "%s sofreu %s de dano e teve seu %s cortado fora! (%s/%s)";
                        break;
                    default:
                        $formato = "%s sofreu %s de dano e teve seu %s esmagado! (%s/%s)";
                        break;
                }
                $mensagem = sprintf(
                    $formato,
                    $personagem->getPersonagem()->getNome(),
                    $danoFinal,
                    $acaoLocal->getLocalStr(),
                    $jogada,
                    $vigor
                );
                $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_SUCESSO, $personagem->getIdPersonagem());
            }
            if (
                $acaoLocal->getLocal() == ItemInfo::PERNA_DIREITA ||
                $acaoLocal->getLocal() == ItemInfo::PERNA_ESQUERDA ||
                $acaoLocal->getLocal() == ItemInfo::PE_DIREITO ||
                $acaoLocal->getLocal() == ItemInfo::PE_ESQUERDO
            ) {
                $personagem->setCodPosicao(CombatePersonagemInfo::POSICAO_CAIDO);
                $mensagem = sprintf("%s caiu no chão!", $personagem->getPersonagem()->getNome());
                $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_SUCESSO, $personagem->getIdPersonagem());
            }
        }
        else {
            $this->aplicarDano($personagem, $acaoLocal->getLocal(), $danoGerado);
            $formato = "%s sofreu %s de dano por %s no %s.";
            $mensagem = sprintf(
                $formato,
                $personagem->getPersonagem()->getNome(),
                $danoGerado,
                $acao->getTipoStr(),
                $acaoLocal->getLocalStr()
            );
            $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_SUCESSO, $personagem->getIdPersonagem());
        }

        return false;
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param AcaoInfo $acao
     * @param AcaoLocalInfo $acaoLocal
     * @param int $danoBasico
     * @return bool
     */
    private function causarDanoTronco($combate, $personagem, $acao, $acaoLocal, $danoBasico) {
        $danoGerado = $this->danoGerado($danoBasico, $acao->getTipo());
        $this->aplicarDano($personagem, $acaoLocal->getLocal(), $danoGerado);

        //$this->alterar($combate);
        $formato = "%s foi atingido no tronco e sofreu %s de dano por %s.";
        $mensagem = sprintf(
            $formato,
            $personagem->getPersonagem()->getNome(),
            $danoGerado,
            $acao->getTipoStr()
        );
        $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_SUCESSO, $personagem->getIdPersonagem());
        return true;
    }


    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param AcaoInfo $acao
     * @param AcaoLocalInfo $acaoLocal
     * @param int $danoBasico
     * @return bool
     */
    private function causarDanoLocal($combate, $personagem, $acao, $acaoLocal, $danoBasico) {
        switch ($acaoLocal->getLocal()) {
            case ItemInfo::BRACO_ESQUERDO:
            case ItemInfo::BRACO_DIREITO:
            case ItemInfo::PERNA_ESQUERDA:
            case ItemInfo::PERNA_DIREITA:
                $vigor = $personagem->getPersonagem()->getVigor();
                $danoMaximo = floor($vigor / 2);
                return $this->causarDanoMembro($combate, $personagem, $acao, $acaoLocal, $danoBasico, $danoMaximo);
                break;
            case ItemInfo::MAO_ESQUERDA:
            case ItemInfo::MAO_DIREITA:
            case ItemInfo::PE_ESQUERDO:
            case ItemInfo::PE_DIREITO:
                $vigor = $personagem->getPersonagem()->getVigor();
                $danoMaximo = floor($vigor / 3);
                return $this->causarDanoMembro($combate, $personagem, $acao, $acaoLocal, $danoBasico, $danoMaximo);
                break;
            default:
                return $this->causarDanoTronco($combate, $personagem, $acao, $acaoLocal, $danoBasico);
                break;
        }
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $atacante
     * @param CombatePersonagemInfo $personagem
     * @param AcaoInfo $acao
     * @param AcaoLocalInfo $acaoLocal
     * @param int $dano
     * @return bool
     */
    private function causarDano($combate, $atacante, $personagem, $acao, $acaoLocal, $dano) {
        $retorno = false;
        $rd = $personagem->getPersonagem()->getRD($acaoLocal->getLocal());
        $danoBasico = $dano - $rd;
        if ($danoBasico >= 0) {
            $formato = "%s atacou %s e conseguiu causar %s de dano básico por %s na %s.";
            $mensagem = sprintf(
                $formato,
                $atacante->getPersonagem()->getNome(),
                $personagem->getPersonagem()->getNome(),
                $dano,
                $acao->getTipoStr(),
                $acaoLocal->getLocalStr()
            );
            $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_FALHOU, $atacante->getIdPersonagem());
            if ($acaoLocal->getLocal() == ItemInfo::CABECA) {
                if (
                    $acao->getTipo() == ItemAtributoInfo::PERFURACAO ||
                    $acao->getTipo() == ItemAtributoInfo::CORTE
                ) {
                    if (($danoBasico - 2) > 0) {
                        $retorno = $this->causarDanoCerebro($combate, $personagem, $acao, $acaoLocal, $danoBasico - 2);
                    }
                    else {
                        $retorno = $this->causarDanoCabeca($combate, $personagem, $acao, $acaoLocal, $danoBasico);
                    }
                }
                else {
                    $retorno = $this->causarDanoLocal($combate, $personagem, $acao, $acaoLocal, $danoBasico);
                }
            }
            else {
                $retorno = $this->causarDanoLocal($combate, $personagem, $acao, $acaoLocal, $danoBasico);
            }
            $this->verificarVida($combate, $personagem);
        }
        else {
            $formato = "%s atacou %s e causou %s de dano por %s na %s mas o dano não passou pela armadura (RD %s).";
            $mensagem = sprintf(
                $formato,
                $atacante->getPersonagem()->getNome(),
                $personagem->getPersonagem()->getNome(),
                $dano,
                $acao->getTipoStr(),
                $acaoLocal->getLocalStr(),
                $rd
            );
            $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_FALHOU, $atacante->getIdPersonagem());
        }
        return $retorno;
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param CombatePersonagemInfo $inimigo
     * @param AcaoInfo $acao
     * @param AcaoLocalInfo $acaoLocal
     * @return bool
     */
    private function atinguirGolpe($combate, $personagem, $inimigo, $acao, $acaoLocal) {

        $dano = $acao->getDano()->jogar();
        return $this->causarDano($combate, $personagem, $inimigo, $acao, $acaoLocal, $dano);
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param CombatePersonagemInfo $inimigo
     * @param AcaoInfo $acao
     * @param AcaoLocalInfo $acaoLocal
     * @return bool
     */
    private function atingirAtaque($combate, $personagem, $inimigo, $acao, $acaoLocal) {

        $situacoes = array(
            CombatePersonagemInfo::SITUACAO_ACORDADO,
            CombatePersonagemInfo::SITUACAO_EVADINDO
        );
        if (in_array($inimigo->getCodSituacao(), $situacoes)) {
            if ($this->executarEsquiva($combate, $inimigo, $acaoLocal->getLocal())) {
                return false;
            }
            if ($this->executarBloqueio($combate, $inimigo)) {
                return false;
            }
            if ($this->executarAparar($combate, $inimigo, ItemInfo::MAO_DIREITA)) {
                return false;
            }
            if ($this->executarAparar($combate, $inimigo, ItemInfo::MAO_ESQUERDA)) {
                return false;
            }
        }
        else {
            $formato = "%s está incapaz de se defender.";
            $mensagem = sprintf($formato, $inimigo->getPersonagem()->getNome());
            $combate->adicionarLog($mensagem, CombateLogInfo::DEFESA_FALHA, $inimigo->getIdPersonagem());
        }
        return $this->atinguirGolpe($combate, $personagem, $inimigo, $acao, $acaoLocal);
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param CombatePersonagemInfo $inimigo
     * @param AcaoInfo $acao
     * @param string $local
     * @return bool
     */
    private function executarAtaqueInterno($combate, $personagem, $inimigo, $acao, $local) {
        $retorno = false;

        if (!$acao->getAtivo()) {
            $formato = "%s não pode executar a ação '%s'.";
            $mensagem = sprintf($formato, $personagem->getPersonagem()->getNome(), $acao->getNome());
            throw new Exception($mensagem);
        }

        $acaoLocal = $acao->getPontoImpacto($local);

        if (is_null($acaoLocal)) {
            throw new Exception(sprintf("Não é possível atacar o local '%s'.", $acaoLocal));
        }

        if (!is_null($acao->getLocal())) {
            if ($acao->getLocal() == ItemInfo::MAO_DIREITA) {
                $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_USADA);
                $personagem->setPernas(CombatePersonagemInfo::MEMBRO_USADA);
            }
            elseif ($acao->getLocal() == ItemInfo::MAO_ESQUERDA) {
                $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_USADA);
                $personagem->setPernas(CombatePersonagemInfo::MEMBRO_USADA);
            }
        }
        if ($acao->getSlug() == AcaoInfo::CHUTE) {
            $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_USADA);
            $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_USADA);
            $personagem->setPernas(CombatePersonagemInfo::MEMBRO_USADA);
        }

        $jogada = DadoBLL::jogar();
        if ($jogada <= $acaoLocal->getNH()) {
            $retorno = $this->atingirAtaque($combate, $personagem, $inimigo, $acao, $acaoLocal);
        }
        elseif ($jogada >= 17) {
            //$this->alterar($combate);
            $formato = "%s tentou atacar %s no %s e sofreu uma falha crítica (%s/%s).";
            $mensagem = sprintf(
                $formato,
                $personagem->getPersonagem()->getNome(),
                $inimigo->getPersonagem()->getNome(),
                $acaoLocal->getLocalStr(),
                $jogada,
                $acaoLocal->getNH()
            );
            $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_FALHA_CRITICA, $personagem->getIdPersonagem());
        }
        else {
            //$this->alterar($combate);
            $formato = "%s tentou atacar %s no %s, mas errou (%s/%s).";
            $mensagem = sprintf(
                $formato, 
                $personagem->getPersonagem()->getNome(),
                $inimigo->getPersonagem()->getNome(),
                $acaoLocal->getLocalStr(),
                $jogada,
                $acaoLocal->getNH()
            );
            $combate->adicionarLog($mensagem, CombateLogInfo::ATAQUE_FALHOU, $personagem->getIdPersonagem());
        }

        return $retorno;
    }

    /**
     * @throws Exception
     * @param int $id_combate
     * @param int $id_personagem
     * @param int $id_inimigo
     * @param string $acaoSlug
     * @param string $local
     * @return bool
     */
    public function executarAtaque($id_combate, $id_personagem, $id_inimigo, $acaoSlug, $local) {
        $combate = $this->pegar($id_combate);

        $personagem = $combate->getPersonagem($id_personagem);
        $inimigo = $combate->getPersonagem($id_inimigo);
        $acoes = $this->listarAcao($combate, $id_personagem);
        $acao = null;
        foreach ($acoes as $a) {
            if ($a->getSlug() == $acaoSlug) {
                $acao = $a;
                break;
            }
        }
        if (is_null($personagem)) {
            throw new Exception(sprintf("Nenhum personagem encontrado com o id %s.", $id_personagem));
        }
        if (is_null($inimigo)) {
            throw new Exception(sprintf("Nenhum inimigo encontrado com o id %s.", $id_inimigo));
        }
        if (is_null($acao)) {
            throw new Exception(sprintf("Nenhuma ação encontrada com o slug %s.", $acaoSlug));
        }
        $retorno = $this->executarAtaqueInterno($combate, $personagem, $inimigo, $acao, $local);
        $this->validarCombate($combate);
        $this->alterar($combate);
        if ($combate->getCodSituacao() == CombateInfo::SITUACAO_EM_COMBATE) {
            $this->verificarTurno($id_combate);
        }
        return $retorno;
    }

    /**
     * @throws Exception
     * @param int $id_combate
     */
    public function verificarTurno($id_combate) {
        $combate = $this->pegar($id_combate);
        $acoes = $this->listarAcao($combate, $combate->getIdPersonagem());
        $temAcao = false;
        foreach ($acoes as $acao) {
            if ($acao->getSlug() != AcaoInfo::TURNO && $acao->getAtivo() == true) {
                $temAcao = true;
                break;
            }
        }
        if (!$temAcao) {
            $this->finalizarTurno($combate);
            //$this->alterar($combate);
        }
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     */
    private function tentarAcordar($combate, $personagem) {
        if ($personagem->getCodSituacao() == CombatePersonagemInfo::SITUACAO_ATORDOADO) {
            $jogada = DadoBLL::jogar();
            $vigor = $personagem->getPersonagem()->getVigor();
            if ($jogada <= $vigor) {

                $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_USADA);
                $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_USADA);
                $personagem->setPernas(CombatePersonagemInfo::MEMBRO_USADA);
                $personagem->setCodSituacao(CombatePersonagemInfo::SITUACAO_ACORDADO);

                $formato = "%s recuperou a conciência (%s/%s).";
                $mensagem = sprintf($formato, $personagem->getPersonagem()->getNome(), $jogada, $vigor);
                $combate->adicionarLog($mensagem, CombateLogInfo::RECUPERAR_CONCIENCIA, $personagem->getIdPersonagem());
            }
            else {
                $formato = "%s ainda continua atordoado (%s/%s).";
                $mensagem = sprintf($formato, $personagem->getPersonagem()->getNome(), $jogada, $vigor);
                $combate->adicionarLog($mensagem, CombateLogInfo::RECUPERAR_CONCIENCIA, $personagem->getIdPersonagem());
            }
        }
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     */
    private function verificarConciencia($combate) {
        $personagem = $combate->getPersonagem($combate->getIdPersonagem());
        $this->tentarAcordar($combate, $personagem);
        foreach ($combate->listarInimigo() as $inimigo) {
            $this->tentarAcordar($combate, $inimigo);
        }
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @return bool
     */
    public function finalizarTurno($combate) {

        foreach ($combate->listarPersonagem() as $personagem) {

            if ($personagem->getMaoDireita() == CombatePersonagemInfo::MEMBRO_USADA) {
                $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_LIVRE);
            }
            if ($personagem->getMaoEsquerda() == CombatePersonagemInfo::MEMBRO_USADA) {
                $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_LIVRE);
            }
            if ($personagem->getPernas() == CombatePersonagemInfo::MEMBRO_USADA) {
                $personagem->setPernas(CombatePersonagemInfo::MEMBRO_LIVRE);
            }
            if ($personagem->getDanoTurno() > 0) {
                $personagem->setImpacto($personagem->getDanoTurno());
                $personagem->setDanoTurno(0);
            }
        }

        $this->agirInimigo($combate);

        $combate->adicionarLog("Turno finalizado.", CombateLogInfo::TURNO_FINALIZADO);
        $this->validarCombate($combate);
        if ($combate->getCodSituacao() == CombateInfo::SITUACAO_EM_COMBATE) {
            $this->verificarConciencia($combate);
        }
        //$this->alterar($combate);
        return true;
    }

    /**
     * @throws Exception
     * @param CombatePersonagemInfo $personagem
     * @return int
     */
    private function calcularFerimento($personagem) {
        $ferimento = 0;
        foreach ($personagem->listarMembro() as $membro) {
            switch ($membro->getCodSituacao()) {
                case CombateMembroInfo::SITUACAO_INCAPACITADO:
                    $ferimento += 10;
                    break;
                case CombateMembroInfo::SITUACAO_DESTRUIDO:
                    $ferimento += 30;
                    break;
            }
        }
        switch ($personagem->getCodSituacao()) {
            case CombatePersonagemInfo::SITUACAO_ATORDOADO:
                $ferimento += 20;
                break;
            case CombatePersonagemInfo::SITUACAO_DESMAIADO:
                $ferimento += 60;
                break;
            case CombatePersonagemInfo::SITUACAO_MORTO:
                $ferimento += 100;
                break;
        }
        $ferimento += $personagem->getVidaGasta();
        return $ferimento;
    }

    /**
     * @throws Exception
     * @param PersonagemInfo $personagem
     * @return int
     */
    private function calcularSaque($personagem) {
        $ouro = 0;
        foreach ($personagem->listarArma() as $arma) {
            if (!is_null($arma->getItem())) {
                $ouro += $arma->getItem()->getOuro() * 0.1;
            }
        }
        foreach ($personagem->listarArmadura() as $armadura) {
            if (!is_null($armadura->getItem())) {
                $ouro += $armadura->getItem()->getOuro() * 0.1;
            }
        }
        $ouro += $personagem->getOuro() * 0.01;
        return floor($ouro);
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     */
    public function validarCombate($combate) {
        $personagem = $combate->getPersonagem($combate->getIdPersonagem());
        if (
            $personagem->getCodSituacao() == CombatePersonagemInfo::SITUACAO_DESMAIADO ||
            $personagem->getCodSituacao() == CombatePersonagemInfo::SITUACAO_MORTO
        ) {
            $combate->setCodSituacao(CombateInfo::SITUACAO_DERROTA);
            $ferimento = $this->calcularFerimento($personagem);
            $xp = 10;

            $regraPersonagem = new PersonagemBLL();
            $pessoa = $regraPersonagem->pegar($personagem->getIdPersonagem());
            $pessoa->setBaseTurno($pessoa->getBaseTurno() - $ferimento);
            $pessoa->setXP($pessoa->getXP() + $xp);
            $regraPersonagem->alterar($pessoa);

            $formato = "Você foi derrotado! Sofreu %s em ferimentos e ganhou %s de XP.";
            $mensagem = sprintf($formato, $ferimento, $xp);

            $combate->adicionarLog($mensagem, CombateLogInfo::COMBATE_DERROTA, $combate->getIdPersonagem());
        }
        $mortos = true;
        foreach ($combate->listarInimigo() as $inimigo) {
            if (
                $inimigo->getCodSituacao() != CombatePersonagemInfo::SITUACAO_DESMAIADO &&
                $inimigo->getCodSituacao() != CombatePersonagemInfo::SITUACAO_MORTO
            ) {
                $mortos = false;
                break;
            }
        }
        if ($mortos == true) {
            $combate->setCodSituacao(CombateInfo::SITUACAO_VITORIA);

            $ferimento = $this->calcularFerimento($personagem);
            $xp = 30;
            $ouro = 0;

            foreach ($combate->listarInimigo() as $inimigo) {
                $ouro += $this->calcularSaque($inimigo->getPersonagem());
            }

            $regraPersonagem = new PersonagemBLL();
            $pessoa = $regraPersonagem->pegar($personagem->getIdPersonagem());
            $pessoa->setBaseTurno($pessoa->getBaseTurno() - $ferimento);
            $pessoa->setXP($pessoa->getXP() + $xp);
            $pessoa->setOuro($pessoa->getOuro() + $ouro);
            $regraPersonagem->alterar($pessoa);

            $formato = "Você venceu! Sofreu %s em ferimentos, ganhou %s de XP e %s de ouro.";
            $mensagem = sprintf($formato, $ferimento, $xp, $ouro);

            $combate->adicionarLog($mensagem, CombateLogInfo::COMBATE_VITORIA );
        }
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param CombatePersonagemInfo $inimigo
     * @return bool
     */
    public function agir($combate, $personagem, $inimigo) {
        $tipoAcao = array(
            AcaoInfo::PEGAR_ARMA_CHAO,
            AcaoInfo::PEGAR_ARMA_COLDRI,
            AcaoInfo::GUARDAR_ARMA,
            AcaoInfo::SOLTAR_ARMA,
            AcaoInfo::TURNO,
            AcaoInfo::EVADIR
        );

        $acoes = $this->listarAcao($combate, $personagem->getIdPersonagem());
        $acaoComSlug = array();
        foreach ($acoes as $acao) {
            $acaoComSlug[$acao->getSlug()] = $acao;
        }
        if ($personagem->getCodPosicao() == CombatePersonagemInfo::POSICAO_CAIDO) {
            if (array_key_exists(AcaoInfo::POSICAO_AGACHAR, $acaoComSlug)) {
                /** @var AcaoInfo $acao */
                $acao = $acaoComSlug[AcaoInfo::POSICAO_AGACHAR];
                if ($acao->getAtivo()) {
                    return $this->executarAcaoInterna($combate, $personagem, $acao);
                }
            }
        }
        if ($personagem->getCodPosicao() == CombatePersonagemInfo::POSICAO_AGACHADO) {
            if (array_key_exists(AcaoInfo::POSICAO_LEVANTAR, $acaoComSlug)) {
                /** @var AcaoInfo $acao */
                $acao = $acaoComSlug[AcaoInfo::POSICAO_LEVANTAR];
                if ($acao->getAtivo()) {
                    return $this->executarAcaoInterna($combate, $personagem, $acao);
                }
            }
        }
        $maos = array(ItemInfo::MAO_DIREITA, ItemInfo::MAO_ESQUERDA);
        $ataques = array();
        foreach ($acoes as $acao) {
            if (!in_array($acao->getSlug(), $tipoAcao) && $acao->getAtivo()) {
                if (in_array($acao->getLocal(), $maos)) {
                    $ataques[] = $acao;
                } elseif ($acao->getSlug() == AcaoInfo::SOCO) {
                    $ataques[] = $acao;
                } elseif ($acao->getSlug() == AcaoInfo::CHUTE) {
                    $ataques[] = $acao;
                }
            }
        }
        if (count($ataques) > 0) {

            //$personagem = $combate->getPersonagem($combate->getIdPersonagem());
            shuffle($ataques);
            /** @var AcaoInfo $ataque */
            $ataque = $ataques[0];
            $locais = $ataque->listarPontoImpacto();
            if (count($locais) > 0) {
                shuffle($locais);
                /** @var AcaoLocalInfo $local */
                $local = reset($locais);
                return $this->executarAtaqueInterno($combate, $personagem, $inimigo, $ataque, $local->getLocal());
            }
            else {
                $mensagem = sprintf("Ataque %s não possui nenhum ponto de impacto.", $ataque->getNome());
                throw new Exception($mensagem);
            }
        }
        return false;
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     */
    public function agirInimigo($combate) {
        foreach ($combate->listarInimigo() as $inimigo) {
            $personagem = $combate->getPersonagem($combate->getIdPersonagem());
            $this->agir($combate, $inimigo, $personagem);
        }
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param string $local
     * @return bool
     */
    public function soltarArma($combate, $personagem, $local) {

        $maos = array(
            ItemInfo::MAO_DIREITA,
            ItemInfo::MAO_ESQUERDA
        );

        if (!in_array($local, $maos)) {
            throw new Exception("Só é possível soltar arma nas mãos.");
        }

        $item = $personagem->getMembro($local)->getItem();
        if (is_null($item)) {
            throw new Exception("Nenhuma arma para ser solta.");
        }

        $personagem->adicionarObjetoChao($item->getSlug());
        $personagem->getMembro($local)->limparItem();

        $formato = "%s jogou a arma %s no chão.";
        $mensagem = sprintf($formato, $personagem->getPersonagem()->getNome(), $item->getNomeCurto());
        $combate->adicionarLog($mensagem, CombateLogInfo::SOLTAR_ARMA, $personagem->getIdPersonagem());

        return true;
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param string $itemSlug
     * @param string $local
     * @return bool
     */
    public function guardarArma($combate, $personagem, $itemSlug, $local) {

        $maos = array(
            ItemInfo::MAO_DIREITA,
            ItemInfo::MAO_ESQUERDA
        );

        $arma = null;
        $maoEquipada = null;
        foreach ($maos as $mao) {
            $item = $personagem->getMembro($mao)->getItem();
            if (!is_null($item) && $item->getSlug() == $itemSlug) {
                $arma = $item;
                $maoEquipada = $mao;
                break;
            }
        }

        if (is_null($arma)) {
            $mensagem = sprintf("%s não está em nenhuma das mãos.", $itemSlug);
            throw new Exception($mensagem);
        }

        $item = $personagem->getMembro($local)->getItem();
        if (!is_null($item)) {
            throw new Exception("Esse coldre já está preenchido.");
        }

        $personagem->getMembro($maoEquipada)->limparItem();
        $personagem->getMembro($local)->limparItem();
        $personagem->getMembro($local)->setItemSlug($arma->getSlug());
        if ($maoEquipada == ItemInfo::MAO_DIREITA) {
            $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_USADA);
        }
        elseif ($maoEquipada == ItemInfo::MAO_ESQUERDA) {
            $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_USADA);
        }

        $regraItem = new ItemBLL();
        $corpo = $regraItem->listarCorpo();

        $formato = "%s guardou a arma %s no coldre da %s.";
        $mensagem = sprintf($formato, $personagem->getPersonagem()->getNome(), $arma->getNome(), $corpo[$local]);
        $combate->adicionarLog($mensagem, CombateLogInfo::GUARDAR_ARMA, $personagem->getIdPersonagem());

        return true;
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param string $itemSlug
     * @param string $local
     * @return bool
     */
    public function sacarArma($combate, $personagem, $itemSlug, $local) {

        $regraItem = new ItemBLL();
        $corpo = $regraItem->listarCorpo();

        $coldres = array(
            ItemInfo::TRONCO,
            ItemInfo::PERNA_DIREITA,
            ItemInfo::PERNA_ESQUERDA
        );

        $item = $personagem->getMembro($local)->getItem();
        if (!is_null($item)) {
            $mensagem = sprintf("%s não está vazia.", $corpo[$local]);
            throw new Exception($mensagem);
        }

        $arma = null;
        $localColdre = null;
        foreach ($coldres as $coldre) {
            $item = $personagem->getMembro($coldre)->getItem();
            if (!is_null($item) && $item->getSlug() == $itemSlug) {
                $arma = $item;
                $localColdre = $coldre;
                break;
            }
        }

        if (is_null($arma)) {
            $mensagem = sprintf("%s não está em nenhum dos coldres.", $itemSlug);
            throw new Exception($mensagem);
        }

        $personagem->getMembro($local)->limparItem();
        $personagem->getMembro($local)->setItemSlug($arma->getSlug());
        if ($local == ItemInfo::MAO_DIREITA) {
            $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_USADA);
        }
        elseif ($local == ItemInfo::MAO_ESQUERDA) {
            $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_USADA);
        }
        $personagem->getMembro($localColdre)->limparItem();

        $formato = "%s sacou sua %s.";
        $mensagem = sprintf($formato, $personagem->getPersonagem()->getNome(), $item->getNomeCurto());
        $combate->adicionarLog($mensagem, CombateLogInfo::SACAR_ARMA, $personagem->getIdPersonagem());

        return true;
    }

    /**
     * @throws Exception
     * @param CombateInfo $combate
     * @param CombatePersonagemInfo $personagem
     * @param string $itemSlug
     * @param string $local
     * @return bool
     */
    public function pegarDoChao($combate, $personagem, $itemSlug, $local) {

        $regraItem = new ItemBLL();
        $corpo = $regraItem->listarCorpo();

        $posicaoValida = array(
            CombatePersonagemInfo::POSICAO_AGACHADO,
            CombatePersonagemInfo::POSICAO_CAIDO
        );

        if (!in_array($personagem->getCodPosicao(), $posicaoValida)) {
            throw new Exception("Para pegar um objeto do chão você tem que estar caído ou agachado.");
        }

        $item = $personagem->getMembro($local)->getItem();
        if (!is_null($item)) {
            $mensagem = sprintf("%s não está vazia.", $corpo[$local]);
            throw new Exception($mensagem);
        }

        $objetosChao = $personagem->listarItemChao();

        $arma = null;
        foreach ($objetosChao as $objeto) {
            if ($objeto->getItemSlug() == $itemSlug) {
                $arma = $objeto->getItem();
                break;
            }
        }

        if (is_null($arma)) {
            $mensagem = sprintf("%s não está no chão.", $itemSlug);
            throw new Exception($mensagem);
        }

        $personagem->getMembro($local)->limparItem();
        $personagem->getMembro($local)->setItemSlug($arma->getSlug());
        $personagem->removerObjetoChao($arma->getSlug());

        if ($local == ItemInfo::MAO_DIREITA) {
            $personagem->setMaoDireita(CombatePersonagemInfo::MEMBRO_USADA);
        }
        elseif ($local == ItemInfo::MAO_ESQUERDA) {
            $personagem->setMaoEsquerda(CombatePersonagemInfo::MEMBRO_USADA);
        }

        $formato = "%s pegou a %s do chão.";
        $mensagem = sprintf($formato, $personagem->getPersonagem()->getNome(), $arma->getNome());
        $combate->adicionarLog($mensagem, CombateLogInfo::PEGAR_DO_CHAO, $personagem->getIdPersonagem());

        return true;
    }

    /**
     * @throws Exception
     * @param CombatePersonagemInfo $personagem
     * @param string $className
     * @param string $icon
     * @return string
     */
    public function toCard($personagem, $className = "card-default", $icon = "ra-wyvern") {
        $html = "";
        $html .= "<div class=\"card " . $className . "\">";
        $html .= "<div class=\"card-title\"><i class=\"ra " . $icon . "\"></i><h3>" .
            $personagem->getPersonagem()->getNome() . "</h3></div>";
        $html .= "<div class=\"card-content\">";
        $html .= "<div class=\"card-image\"><span class=\"card-ui-right\">";
        $html .= "<i class=\"fa fa-heart\"></i> ". $personagem->getVida() ." / " .
            $personagem->getPersonagem()->getVida() . "<br />";
        $html .= "<i class=\"ra ra-water-drop\"></i> ". $personagem->getFadiga() ." / " .
            $personagem->getPersonagem()->getFadiga() . "<br />";
        switch ($personagem->getCodPosicao()) {
            case CombatePersonagemInfo::POSICAO_EM_PE:
                $html .= "<i class=\"ra ra-player\"></i> Em pé";
                break;
            case CombatePersonagemInfo::POSICAO_AGACHADO:
                $html .= "<i class=\"ra ra-player-shot\"></i> Agachado";
                break;
            case CombatePersonagemInfo::POSICAO_CAIDO:
                $html .= "<i class=\"ra ra-falling\"></i> Caído";
                break;
        }
        $html .= "</span><span class=\"card-ui-left\">";
        switch ($personagem->getCodSituacao()) {
            case CombatePersonagemInfo::SITUACAO_ATORDOADO:
                $html .= "<i class=\"ra ra-player-pain\"></i> Atordoado<br />";
                break;
            case CombatePersonagemInfo::SITUACAO_DESMAIADO:
                $html .= "<i class=\"ra ra-player-pain\"></i> Desmaiado<br />";
                break;
            case CombatePersonagemInfo::SITUACAO_EVADINDO:
                $html .= "<i class=\"ra ra-player-dodge\"></i> Evadindo<br />";
                break;
        }
        foreach ($personagem->listarMembro() as $membro) {
            if ($membro->getCodSituacao() != CombateMembroInfo::SITUACAO_NORMAL) {
                $html .= "<i class=\"ra ra-broken-bone\"></i> " . $membro->getLocalStr() . "<br />";
            }
        }
        $html .= "</span></div><div class=\"left-hand\">";
        $maoDireita = $personagem->getMembro(ItemInfo::MAO_DIREITA)->getItem();
        $maoEsquerda = $personagem->getMembro(ItemInfo::MAO_ESQUERDA)->getItem();
        $html .= "esq<br />";
        if (!is_null($maoEsquerda)) {
            if ($maoEsquerda->getTipo() == ItemInfo::ESCUDO) {
                $html .= "<i class=\"ra ra-5x ra-shield\"></i>";
            }
            else {
                $html .= "<i class=\"ra ra-5x ra-sword\"></i>";
            }
        }
        else {
            $html .= "<i class=\"ra ra-5x ra-hand\"></i>";
        }
        $html .= "</div><div class=\"right-hand\">dir<br />";
        if (!is_null($maoDireita)) {
            $html .= "<i class=\"ra ra-5x ra-sword\"></i>";
        }
        else {
            $html .= "<i class=\"ra ra-5x ra-hand\"></i>";
        }
        $html .= "</div>";
        $html .= "<div class=\"card-body\"><h5><i class=\"ra ra-shield\"></i> Defesa Ativa</h5>";
        $html .= "<div class=\"row\">";
        $html .= "<div class=\"col-4\"><i class=\"ra ra-2x ra-player-dodge\"></i><br /><span>";
        $html .= $personagem->getEsquiva();
        $html .= "</span></div>";
        $html .= "<div class=\"col-4\"><i class=\"ra ra-2x ra-shield\"></i><br /><span>";
        $html .= $personagem->getBloqueio() . "</span></div>";
        $html .= "<div class=\"col-4\"><i class=\"ra ra-2x ra-crossed-axes\"></i><br /><span>";
        $html .= $personagem->getAparar(ItemInfo::MAO_ESQUERDA);
        if ($personagem->getAparar(ItemInfo::MAO_DIREITA) > 0) {
            $html .= " / " . $personagem->getAparar(ItemInfo::MAO_DIREITA);
        }
        $html .= "</span></div></div>";
        $html .= "<h5><i class=\"ra ra-sword\"></i> Ataques</h5>
                    Cortar (0-5 corte), Perfurar (0-3 perfuração)";
        $itens = $personagem->listarItemChao();
        if (count($itens) > 0) {
            $html .= "<h5><i class=\"ra ra-arena\"></i> Chão</h5>";
            $armas = array();
            foreach ($itens as $item) {
                $arma = $item->getItem()->getNome();
                if ($item->getQuantidade() > 1) {
                    $arma .= "(" . $item->getQuantidade() . ")";
                }
                $armas[] = $arma;
            }
            $html .= implode(", ", $armas);
        }
        $html .= "</div></div></div>";
        return $html;
    }


    /**
     * @param CombateInfo $combate
     * @return bool
     * @throws Exception
     */
    public function automatico($combate) {
        $i = 0;
        while ($combate->getCodSituacao() == CombateInfo::SITUACAO_EM_COMBATE) {
            $personagem = $combate->getPersonagem($combate->getIdPersonagem());
            $inimigos = $combate->listarInimigo();
            shuffle($inimigos);
            /** @var CombatePersonagemInfo $inimigo */
            $inimigo = array_values($inimigos)[0];
            $this->agir($combate, $personagem, $inimigo);
            $this->finalizarTurno($combate);
            $i++;
            if ($i > 200) {
                $combate->adicionarLog(
                    "Limite de 200 turnos atingido.",
                    CombateLogInfo::TURNO_FINALIZADO
                );
                break;
            }
        }
        $this->alterar($combate);
        return true;
    }
}

