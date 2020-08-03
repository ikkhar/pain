<?php
/**
 * Created by PhpStorm.
 * User: Utilisateur
 * Date: 04/06/2020
 * Time: 09:08
 */
class Pain extends Patissier
{
private $_farine;

    /**
     * Pain constructor.
     * @param $_farine
     */
    public function __construct($_farine)
    {
        $this->_farine = $_farine;
    }

    /**
     * @return mixed
     */
    public function getFarine()
    {
        return $this->_farine;
    }

    /**
     * @param mixed $farine
     */
    public function setFarine($farine)
    {
        $this->_farine = $farine;
    }

}

//--------------------------antisèche fichier ajax-------------------------------------------------
<?php
include '../../models/functions_session.php';
load_session("telecons", 10800);
include "../controllers/autoloader.php";

//if (file_exists("../../controllers/db.php")){
//    include "../../controllers/db.php";
//}

$liste_consultant = false;

if ($action = Tools::Post('a')) {
    switch ($action) {
        case 'load_page_client':
            //Tools::error_dump($action,"a");
            echo json_encode(load_page_client());
            break;
        case 'setData_updateElementForm':
            //Tools::error_dump($action,"a");
            echo json_encode(setData_updateElementForm());
            break;
        case "update_justification":
            update_justification();
            break;
        case "update_info":
            update_info();
            break;
        case "closeDemande":
            closeDemande();
            break;
        case "closeRdv":
            closeRdv();
            break;

    }
}
// fonction qui récupere les infos client
function load_page_client(){
    $retour = [];
    $retour['infos'] = getData_infos();
    //$retour['option_client'] = getData_option_client();
    $retour['phones'] = getData_phones();
    $retour['stats'] = getData_stats();
    $retour['histo_consult'] = getData_histo_consult();
    $retour['cartes'] = getData_cartes();
    $retour['demandes'] = getData_demandes();
    $retour['rdv'] = getData_rdv();

    return $retour;


}
//récupère les données infos
function getData_infos(){

    $retour = [];
    if ($id = Tools::Post('id')) {

        $Client = new Client($id);
        //var_dump($Client);
        $retour['id'] = $Client->getId();
        $retour['genre'] = $Client->getGenre();
        $retour['nom'] = $Client->getNom();
        $retour['prenom'] = $Client->getPrenom();
        $retour['mail'] = $Client->getEmail();
        $retour['ddn'] = $Client->getDdn();
        $retour['pays'] = $Client->getPaysNaissance();
        $retour['cp'] = $Client->getCp();
        $retour['ville'] = $Client->getVille();
        $retour['adr1'] = $Client->getAddress1();
        $retour['adr2'] = $Client->getAddress2();
        $retour['infos_client'] = $Client->getInfos();
        $retour['vip'] = $Client->getIsVip();
        $retour['sms_cons'] = $Client->getSmsConfirm();
        $retour['sms_anniv'] = $Client->getSmsAnniversaire();
        $retour['interdit'] = $Client->getInterdit();
        $retour['interdit_dette'] = $Client->getDette();
        $retour['coupure'] = $Client->getLimitTpe();
        $retour['blacklist'] = $Client->getIsBlacklist();
    }




    return $retour;


}
//récupère les données telephones
function getData_phones()
{
    if ($id = Tools::Post('id')) {
        global $db;

        $retour = [];
        $sql = "SELECT clientPhone_id,client_id, clientPhone_phone, principal, checked, `type` as type_de_num  FROM clientPhone  WHERE client_id = $id ";

        $q = $db->prepare($sql);

        if ($q->execute()) {
            //error_log("DEBUG ".__LINE__." | ".__FILE__." | RQ MAGIK : ".$sql, 0);
            if($r = $q->fetchAll(PDO::FETCH_OBJ)){
                foreach ($r as $key => $consult) {
                    $retour[$key]['phone_id'] = $consult->principal;
                    $retour[$key]['principal'] = $consult->principal;
                    $retour[$key]['numero'] = Tools::formatPhone($consult->clientPhone_phone, true);
                    $retour[$key]['type'] = $consult->type_de_num;
                    $retour[$key]['check'] = $consult->checked;


                }
                return $retour;
            }
        }


    }
    error_log("ERREUR ".__LINE__." | ".__FILE__."| RQ MAGIK : ".$sql, 0);
    return [];

}
//récupère les données stats
function getData_stats(){
    if ($id = Tools::Post('id')){
        global $db;
        $retour = [];
        $retour['score_client'] = "";
        $retour['panier'] = ""; //1
        $retour['solde'] = "";//1
        $retour['first'] = "";//1
        $retour['taux_cb'] = "";
        $retour['taux_auto'] = "";
        $retour['retard'] = "";//1
        $retour['impaye'] = "";//1
        $retour['consult_cb'] = "";//1
        $retour['consult_auto'] = "";//1
        $retour['consult_web'] = "";//1
        $retour['consult_id']="";//2
        $retour['consultant1']="";//2
        $retour['consultant2']="";//2
        $retour['total_consult'] ="";



        $sql = "SELECT *, UNIX_TIMESTAMP(date_start) as start  FROM consultation  WHERE client_id = $id ORDER BY date_start";

        $q = $db->prepare($sql);
        if($q->execute()) {
            $r = $q->fetchAll(PDO::FETCH_OBJ);
            $list_appel_id = [];
            $panier =0;
            $solde = 0;
            $date_first = "";
            $impaye = 0;
            $montant_du = 0;
            $consult_cb = 0;
            $consult_auto = 0;
            $consult_web = 0;
            $array_cons = [];
            $object_cons = [];
            $cons_1 = new stdClass();
            $cons_1->nb = 0;
            $cons_1->id = 0;
            $cons_2 = new stdClass();
            $cons_2->nb = 0;
            $cons_2->id = 0;
            $tab_consult =[];
            if($r){
                // récuperation des donnéées stats
                foreach ($r as $key => $consult){
                    if ($key == 0){
                        $date_first = Tools::timestptoddn($consult->start);
                    }
                    if (isset($array_cons[$consult->cons_id])){
                        $array_cons[$consult->cons_id]++;
                    }
                    else{
                        $array_cons[$consult->cons_id] = 1;
                    }

                    $solde += $consult->montant_paye;
                    $montant_du += $consult->montant;
                    $impaye++;
                    switch ($consult->type_service){
                        case 0:
                            $consult_cb++;
                            break;
                        //case 1:
                        //    break;
                        case 2:
                            $consult_auto++;
                            break;
                        case 3:
                            $consult_web++;
                            break;
                        default:
                            break;
                    }

                    $list_appel_id[] = $consult->appel_id;

                }
                // récuperation des id des deux consultants les plus solicités
                foreach ($array_cons as $k => $v){
                    $cons_temp = new stdClass();
                    $cons_temp->nb = 0;
                    $cons_temp->id = 0;
                    if ($v > $cons_1 -> nb){
                        if ($cons_1 -> nb == 0){
                            $cons_1 -> nb = $v;
                            $cons_1 -> id = $k;
                        }
                        else{
                            $cons_temp->nb = $cons_1 -> nb;
                            $cons_temp->id =  $cons_1 -> id;

                            $cons_1 -> nb = $v;
                            $cons_1 -> id = $k;
                            $cons_2->nb = $cons_temp -> nb;
                            $cons_2->id =  $cons_temp -> id;

                        }
                    }
                    else{
                        if (($v > $cons_2->nb) && ($v < $cons_1 -> nb )){
                            $cons_2->nb =$v;
                            $cons_2->id =$k;
                        }
                    }

                }
                $id_cons_1 = $cons_1->id;
                $id_cons_2 = $cons_2->id;
                $panier = ($solde/100) / ($consult_cb+$consult_auto+$consult_web);
                $retour['panier'] = round($panier,2); //1
                $retour['solde'] = round($solde/100,2);//1
                $retour['first'] = $date_first;//1
                $retour['retard'] = round(($montant_du /100)-($solde/100),2);//1
                $retour['impaye'] = $impaye;//1
                $retour['consult_cb'] = $consult_cb;//1
                $retour['consult_auto'] = $consult_auto;//1
                $retour['consult_web'] = $consult_web;//1
                $retour['consultant_id_1']= $cons_1->id;//2
                $retour['consultant_id_2']= $cons_2->id;//2

            }
        }
        else{
            error_log("ERREUR ".__LINE__." | ".__FILE__."| RQ MAGIK : ".$sql, 0);
        }
        if (!isset($id_cons_1)){
            $id_cons_1 = 0;
        }
        if (!isset($id_cons_2)){
            $id_cons_2 = 0;
        }
        // récuperation des pseudos des deux consultants les plus solicités
        $sql2 = "SELECT id, pseudo FROM users WHERE id IN ($id_cons_1,$id_cons_2)";
        $q2 = $db->prepare($sql2);
        if($q2->execute()) {
            $r2 = $q2->fetchAll(PDO::FETCH_OBJ);

            foreach ($r2 as $key => $consult) {
                $tab_consult[$key]['nom'] = $consult->pseudo;
                $tab_consult[$key]['id'] = $consult->id;

            }
            if (isset($tab_consult[0])){
                $retour['consultant_pseudo_1']= $tab_consult[0]['nom'];//2
            }
            else{
                $retour['consultant_pseudo_1']= '-';//2
            }
            if (isset($tab_consult[0])){
                $retour['consultant_pseudo_2']=  $tab_consult[1]['nom'];//2
            }
            else{
                $retour['consultant_pseudo_2']= '-';//2
            }


        }

        else{
            error_log("ERREUR ".__LINE__." | ".__FILE__."| RQ MAGIK : ".$sql2, 0);
        }

        $sql3 = "SELECT id, service_type FROM  calls WHERE client_id = $id";
        $q3 = $db->prepare($sql3);
        if($q3->execute()) {
            $r3 = $q3->fetchAll(PDO::FETCH_OBJ);
            $compteur_bc =0;
            $compteur_auto =0;
            foreach ($r3 as $key => $consult) {
                if($consult->service_type == 0){
                    $compteur_bc++;
                }
                if($consult->service_type == 2){
                    $compteur_auto++;
                }

            }
            if ($compteur_bc != 0){
                $retour['taux_cons_cb']= round($consult_cb / $compteur_bc,2)*100 ;//2
            }
            else{
                $retour['taux_cons_cb']= [];
            }
            if ($compteur_auto != 0){
                $retour['taux_cons_auto']= round($consult_auto / $compteur_auto,2)*100 ;//2
            }
            else{
                $retour['taux_cons_auto']= [];
            }


        }

        else{
            error_log("ERREUR ".__LINE__." | ".__FILE__."| RQ MAGIK : ".$sql2, 0);
        }







        return $retour;

    }
    return [];
}
//récupère les données historique consultation
function getData_histo_consult()
{
    if ($id = Tools::Post('id')) {
        global $db,$liste_consultant;
        $retour = [];
        //$liste_consultant = [];
        $liste_consultation = [];
        $liste_cabinet = [];
        $liste_promo = [];

        if($liste_consultant === false){
            $sql = "SELECT id, pseudo FROM users WHERE type = 1";
            $q = $db->prepare($sql);
            if ($q->execute()) {
                $r = $q->fetchAll(PDO::FETCH_OBJ);
                $liste_consultant=[];
                if($r){
                    foreach ($r as $key => $consultant) {
                        $liste_consultant[$consultant->id] = $consultant->pseudo;
                    }
                }

            } else {
                error_log("ERREUR " . __LINE__ . " | " . __FILE__ . "| RQ MAGIK : " . $sql, 0);
            }
        }

        $sql2 = "SELECT id, partner_name FROM services";
        $q2 = $db->prepare($sql2);
        if ($q2->execute()) {

            $r2 = $q2->fetchAll(PDO::FETCH_OBJ);

            foreach ($r2 as $key => $cabinet) {
                $liste_cabinet[$cabinet->id] = $cabinet->partner_name;
            }


        } else {
            error_log("ERREUR " . __LINE__ . " | " . __FILE__ . "| RQ MAGIK : " . $sql2, 0);
        }

        $interdit = false;
        $sql3 = "SELECT interdit_dette FROM clients WHERE id = $id";
        $q3 = $db->prepare($sql3);
        if ($q3->execute()) {
            $r3 = $q3->fetch(PDO::FETCH_OBJ);
            if ($r3){
                if($r3->interdit_dette){
                    $interdit = true;
                }
            }



        } else {
            error_log("ERREUR " . __LINE__ . " | " . __FILE__ . "| RQ MAGIK : " . $sql2, 0);
        }


        $sql5 = "SELECT id, date_start, date_end,statut, cons_id, service_id,type_service, statut_paiement, type_paiement,date_cb_diff,UNIX_TIMESTAMP(date_cb_diff) as date_diff, montant,code_promo, montant_promo, montant_paye , UNIX_TIMESTAMP(date_start) as start, UNIX_TIMESTAMP(date_end) as end, TIMESTAMPDIFF(SECOND,date_start,date_end) as duree FROM consultation  WHERE client_id = $id";
        $q5 = $db->prepare($sql5);
        if ($q5->execute()) {
            if ($r5 = $q5->fetchAll(PDO::FETCH_OBJ)) {
                $liste_consultation = $r5;

                if (count($liste_consultation) > 0) {
                    //récuperer liste de tout les codes promos avec le % correspondant (table promo, table tarif cb)
                    $tab_promo = Promo::getAllPromocodePercentage();
                    //Tools::error_dump($tab_promo);
                    //parcourir le resultat et formater en tableau


                    foreach ($liste_consultation as $key => $consult) {

                        $retour[$key]['id'] = $consult->id;
                        $retour[$key]['date'] = Tools::timestptoddnHis($consult->start, true);
                        $retour[$key]['timestamp'] = $consult->start;
                        $retour[$key]['statut'] = $consult->statut;
                        $retour[$key]['duree'] = gmdate("H:i:s", $consult->duree);
                        $retour[$key]['duree_calcul'] = $consult->duree;
                        $retour[$key]['cons_id'] = $consult->cons_id;
                        $retour[$key]['cons_pseudo'] = '-';
                        //si l'id du consultant exist on recupere le pseudo grace a l'id en index de la liste des consultants
                        if (isset($liste_consultant[$consult->cons_id])) {
                            $retour[$key]['cons_pseudo'] = $liste_consultant[$consult->cons_id];
                        }
                        $retour[$key]['cabinet_id'] = $consult->service_id;
                        $retour[$key]['cabinet'] = '-';
                        //si l'id du cabinet exist on recupere le nom grace a l'id en index de la liste des cabi,et
                        if (isset($liste_cabinet[$consult->service_id])) {
                            $retour[$key]['cabinet'] = $liste_cabinet[$consult->service_id];
                        }
                        $retour[$key]['type_service'] = $consult->type_service;
                        $retour[$key]['statut_paiement'] = $consult->statut_paiement;
                        $retour[$key]['type_paiement'] = $consult->type_paiement;
                        $retour[$key]['date_cb_diff'] = '-';
                        if($consult->date_diff != null){
                            $retour[$key]['date_cb_diff'] = Tools::timestptoddnHis($consult->date_diff);
                        }

                        $retour[$key]['montant'] = ($consult->montant) / 100;
                        $retour[$key]['code_promo_id'] = $consult->code_promo;
                        $retour[$key]['code_promo_pourcentage'] = '-';
                        //si l'id du cabinet exist on recupere le nom grace a l'id en index de la liste des cabi,et
                        if (isset($tab_promo[$consult->code_promo])) {
                            $retour[$key]['code_promo_pourcentage'] = $tab_promo[$consult->code_promo]."%";
                        }
                        $retour[$key]['promo'] = ($consult->montant_promo) / 100;
                        $retour[$key]['montant_du'] = (($consult->montant) / 100) - (($consult->montant_promo) / 100);
                        $retour[$key]['montant_paye'] = ($consult->montant_paye) / 100;
                        $retour[$key]['reste'] = round((($consult->montant) / 100) - (($consult->montant_promo) / 100) - (($consult->montant_paye) / 100),2);
                        $retour[$key]['interdit_dette'] = $interdit;
                    }

                    //Tools::error_dump($retour);
                    return $retour;
                }
            } else {
                error_log("ERREUR " . __LINE__ . " | " . __FILE__ . "| RQ MAGIK : " . $sql, 0);
            }

            return [];
        }
    }
}
//récupère les données cb
function getData_cartes(){

    $retour = [];
    if ($id = Tools::Post('id')) {
        $tabCard = Card::getCardsClient($id,true);

        foreach ($tabCard as $key => $cb){

            $retour[$key]['id'] =$cb['id'];
            $retour[$key]['principale'] = $cb['principale'];
            $retour[$key]['alias'] = $cb['alias'];
            $retour[$key]['type'] = $cb['brand'];
            $retour[$key]['exp_month'] = $cb['date_ed_month'];
            $retour[$key]['exp_year'] = $cb['date_ed_year'];
            $retour[$key]['last_number'] = $cb['num_last'];
            $retour[$key]['owner'] = $cb['owner_name'];
            $retour[$key]['cvc'] = $cb['cvc'];
            $retour[$key]['is_delete'] = $cb['is_delete'];
        }


    return $retour;

    }
    else {
        return [];
    }
};
//récupère les données demande client
function getData_demandes(){
    if ($id = Tools::Post('id')) {
        global $db;
        $retour = [];
        $sql = "SELECT id, UNIX_TIMESTAMP(date_insertion) as date,consultant_id, consultant_name, info_demande,justification,UNIX_TIMESTAMP(last_justif_update) as justif_update, is_complete FROM demande_client  WHERE client_id = $id AND is_complete = 0";
        $q = $db->prepare($sql);
        //error_log("notice ".__LINE__." | ".__FILE__." | RQ MAGIK : ".$sql, 0);
        if ($q->execute()) {
            //error_log("DEBUG ".__LINE__." | ".__FILE__." | RQ MAGIK : ".$sql, 0);
            if($r = $q->fetchAll(PDO::FETCH_OBJ)){
                foreach ($r as $key => $consult) {

                    $retour[$key]['id'] = $consult->id;
                    $retour[$key]['date'] = Tools::timestptoddnHis($consult->date, true);
                    $retour[$key]['timestamp'] = $consult->date;
                    $retour[$key]['consultant_name'] = $consult->consultant_name;
                    $retour[$key]['consultant_id'] = $consult->consultant_id;
                    $retour[$key]['justification'] = $consult->justification;
                    $retour[$key]['info_demande'] = $consult->info_demande;
                    $retour[$key]['maj_justification'] = Tools::timestptoddnHis($consult->justif_update, true);
                    $retour[$key]['option'] = $consult->is_complete;


                }

            }
            return $retour;
        }

        error_log("ERREUR ".__LINE__." | ".__FILE__."| RQ MAGIK : ".$sql, 0);
    }

    return [];


}
//récupère les données rendez-vous client
function getData_rdv(){

    if ($id = Tools::Post('id')) {
        global $db, $liste_consultant;

        if($liste_consultant === false){
            $sql = "SELECT id, pseudo FROM users WHERE type = 1";
            $q = $db->prepare($sql);
            if ($q->execute()) {
                $r = $q->fetchAll(PDO::FETCH_OBJ);
                $liste_consultant=[];
                if($r){
                    foreach ($r as $key => $consultant) {
                        $liste_consultant[$consultant->id] = $consultant->pseudo;
                    }
                }

            } else {
                error_log("ERREUR " . __LINE__ . " | " . __FILE__ . "| RQ MAGIK : " . $sql, 0);
            }
        }

        $sql = "SELECT id, pseudo FROM users WHERE type = 2";
        $q = $db->prepare($sql);
        if ($q->execute()) {

            $r = $q->fetchAll(PDO::FETCH_OBJ);

            foreach ($r as $key => $telecons) {
                $liste_telecons[$telecons->id] = $telecons->pseudo;
            }


        } else {
            error_log("ERREUR " . __LINE__ . " | " . __FILE__ . "| RQ MAGIK : " . $sql, 0);
        }

        $retour = [];
        $sql2 = "SELECT id,date_rdv, UNIX_TIMESTAMP(date_rdv) as date,heure_rdv,minute_rdv,cons_id,client_id, telecons_id, statut FROM rdv  WHERE client_id = $id AND statut = 0 ";
        $q2 = $db->prepare($sql2);

        if ($q2->execute()) {
            //error_log("DEBUG ".__LINE__." | ".__FILE__." | RQ MAGIK : ".$sql, 0);
            if ($r2 = $q2->fetchAll(PDO::FETCH_OBJ)) {
                foreach ($r2 as $key => $consult) {

                    $retour[$key]['id'] = $consult->id;
                    $retour[$key]['date'] = Tools::timestptoddnHis($consult->date_rdv, false);
                    $retour[$key]['timestamp'] = $consult->date;
                    $retour[$key]['heure'] = Tools::two_digit($consult->heure_rdv) .":".Tools::two_digit($consult->minute_rdv);
                    $retour[$key]['consultant_id'] = $consult->cons_id;
                    $retour[$key]['consultant_name'] = $liste_consultant[$consult->cons_id];
                    $retour[$key]['telecons_id'] = $consult->telecons_id;
                    $retour[$key]['telecons_name'] = $liste_telecons[$consult->telecons_id];
                    $retour[$key]['icon_sms'] = Client::get_client_mobile_phone($consult->client_id, true);
                    $retour[$key]['option'] = "à faire plus tard";


                }
                return $retour;
            }
            return [];
        }
    }
    error_log("ERREUR ".__LINE__." | ".__FILE__."| RQ MAGIK : ".$sql, 0);
    return [];
}
//Mise a jour des infos clients
function setData_updateElementForm()
{
//error_log("NOTICE ".__LINE__." | ".__FILE__." | biscotte ", 0);
    if ($client_id = Tools::Post('client_id')) {

        if ($element = Tools::Post('element')) {

            if (!is_bool($value = Tools::Post('value'))) {

                if($value === "@#!?"){
                    $value = "";
                }
                if (Client::updateChamp2($client_id, $element, $value)) {

                    // traitement speciale pour le blacklistage des clients
                    if($element == 'blacklist'){
                        if(isset($_SESSION['userInfos']) && !empty($_SESSION['userInfos'])){
                            $user_id = $_SESSION['userInfos'];
                        }else{
                            $user_id = 0;
                        }
                        if($value == 1){
                            if(Blacklist::addBlacklistNumByClient($client_id,$user_id)){
                                return true;
                            }
                        }else{
                            if(Blacklist::deleteBlacklistNumByClient($client_id)){
                                return true;
                            }
                        }
                    }
                    return false;
                }
            }
        }
    }
    error_log("NOTICE ".__LINE__." | ".__FILE__." | ".$_POST['client_id'], 0);
    return true;
}
//Mise a jour des justification des demandes client
function update_justification(){
    if($demande_id = Tools::Post('demande_id')){
        if($justif_id = Tools::Post('justif_id')){
            if($justif_id == '@#!?'){
                $justif_id = 0;
            }
            if(DemandeClient::updateJustification($justif_id,$demande_id)){
                return true;
            }
        }
    }
    return false;
}
//Mise a jour des infos ( text area) des demandes client
function update_info(){
    if($demande_id = Tools::Post('demande_id')){
        if($infos = Tools::Post('info')){
            //Tools::error_dump($infos);
            if($infos == "@#!?"){
                $infos = "";
            }
            if(DemandeClient::updateInfo($infos,$demande_id)){
                return true;
            }
        }
    }
    return false;
}
// Fermeture des demandes clients
function closeDemande(){
    $demandeId = (int)$_POST['demandeId'];
    $retour = [];
    if(DemandeClient::close($demandeId)){
        $retour['demandes'] = getData_demandes();
        $retour['error'] = false;

    }else{
        $retour['error'] = true;
    }

    echo json_encode($retour);
}
// Suppression des rdv
function closeRdv(){
    $rdvId = (int)$_POST['rdvId'];
    $retour = [];
    if(Rdv::staticDelete($rdvId)){
        $retour['rdv'] = getData_rdv();
        $retour['error'] = false;

    }else{
        $retour['error'] = true;
    }

    echo json_encode($retour);
}
// sms report de rdv
function send_sms_report(){
    //error_log("NOTICE ".__LINE__." | ".__FILE__." | REPORT", 0);
    if(($r = Tools::Post('r')) && ($h = Tools::Post('h')) && ($p = Tools::Post('p')) && ($c = Tools::Post('c'))){

        $content = "Bonjour $p, votre rendez-vous avec $c à $h est retardé. Nous vous recontacterons dès que votre consultant est disponible. Merci.";

        $Sms = new Sms_mtarget(1, [$r], 15, $content);
        if($Sms->sendSms(true)){
            return ["error"=>false];
        }
        return ["error"=>true,"error_str"=>"The message can't be send"];
    }
    return ["error"=>true,"error_str"=>"At least one parameter error"];
}
// sms suppression de rdv
function send_sms_confirm(){
    //error_log("NOTICE ".__LINE__." | ".__FILE__." | CONFIRM", 0);
    if(($r = Tools::Post('r')) && ($h = Tools::Post('h')) && ($p = Tools::Post('p')) && ($c = Tools::Post('c')) && ($d = Tools::Post('d'))){

        $content = "Bonjour $p, nous vous confirmons votre rendez-vous avec $c le $d à $h. Belle journée.";

        $Sms = new Sms_mtarget(1, [$r], 15, $content);
        if($Sms->sendSms(true)){
            return ["error"=>false];
        }
        return ["error"=>true,"error_str"=>"The message can't be send"];
    }
    return ["error"=>true,"error_str"=>"At least one parameter error"];
}

?>

//--------------------------antisèche fichier js-------------------------------------------------

var saferequest = null
var table1
var table2
var table3 = false
// création de l'objet client
var client = new Client();
// assignation de l'id a l'objet client
client._setId($_GET('client_id'))

function Client() {
    this.id = null;
    this.card = [];
    this.last_card_id = null;

    this._setId = function(id){
        this.id = id;
    };

    this._setLastCardId = function(card_id){
        this.last_card_id = card_id;
    };

    this._setNewCard = function (num_card, month, year, owner) {
        this.card.num_card = num_card;
        this.card.month = month;
        this.card.year = year;
        this.card.owner = owner;
        this.card.new = true;
    };

    this._setCardId = function(card_id){
        this.card.id = card_id;
    };

    this._setCvc = function (cvc){
        this.card.cvc = cvc;
    };

    this._setAmount = function (amount){
        this.card.amount = amount;
    };

    this._setPayid = function (payid){
        this.card.payid = payid;
    };

    this._setCard4last = function (last_num){
        this.card.last_num = last_num;
    };

    this._setCardLastNcerror = function (ncerror){
        this.card.last_ncerror = parseInt(ncerror);
    }

    this._setCardLastDateTransaction = function (date_insert){
        this.card.last_date_transaction = date_insert;
    }

    this._resetCard = function (){
        this.card = [];
    };

    this._setData = function(data){
        this.data = data;
    };

    this._setTypePaiement = function(type_paiement){
        this.type_paiement = type_paiement
    }
}

$( document ).ready(function() {
    theme = $("#sessionOrigin").val()

    // chargement du score client
    loadChart(theme)
    //$('#tableDemandeClient .dataTables_filter input').addClass("modifyMargin70")
    load_page_client()
    // Appel des handlers
    setHandlers()
});

// récuperation des datas pour la page client
function load_page_client(){
    if (client.id){
        //requete ajax globale
        $.post("./ajax/client_complete.php",{
            a:'load_page_client',
            id: client.id
        },(data)=>{
           // chargement des infos client
            gen_infos_client(data.infos)
            // chargement des statistiques client
            gen_stats_client(data.stats)
            //chargement du datatable historique client
            getTableDataHisto(data.histo_consult)
            // chargement du datatable demande client
            getTableDataDemandes(data.demandes)
            // chargement du datatable RDV client
            getTableDataRDV(data.rdv)
            // chargement des infos telephone
            gen_phone_client(data.phones)
            // chargement des cartes banquaires
            gen_card_client(data.cartes)

        },"json")
    }
    // else{
    //     console.log('no client_id')
    // }


}
// mise en place des handlers
function setHandlers(){

    //Update DB des formulaires et checkbox

    //mise a jour des selects
    $(document).on('change','.cc_infos select', function () {

        setData_updateElementForm(client.id, $(this).attr('data-db'), $(this).val());
    })
    //mise a jour des inputs
    $(document).on('input updateAddress','.cc_infos input, .cc_infos textarea', function () {
        input = $(this).attr('data-db')
        if (input){
            input_value = $(this).val()
            switch (input){
                case 'nom':
                case 'ville':
                case 'adresse':
                    $(this).val(input_value.toUpperCase())
                    break;
                default:
                    break;

            }
            let control = validator_form_control($(this), "data-db");
            if(control === ""){
                //Système pour gérer le contenu vide
                control = "@#!?";
            }

            setData_updateElementForm(client.id, $(this).attr('data-db'), control);
        }


    })
    //mise a jour des toggles
    $(document).on('input', '#cc_options input', function () {
        let checked = 0;
        if($(this).prop('checked')){
            checked = 1;
        }
        if($(this).attr('data-db') == 'blacklist' && $(this).prop('checked')){
            if(confirm("Voulez-vous vraiment blacklister ce client ? (Il ne pourra plus joindre les services secrétariat et Automate)")){
                setData_updateElementForm(client.id, $(this).attr('data-db'), checked, "data-db");
            }else{
                $(this).prop('checked',false)
                $('#check_isBlacklist_label_cc').html('Est blacklisté')
            }
        }else{
            setData_updateElementForm(client.id, $(this).attr('data-db'), checked, "data-db");
        }


    })
    // fonction de l'autocomplétion de l'adresse dans la barre recherche adresse
    init_autocomplete_address('#in_adr_srch', function (item) {
       // console.log(item)
        let numero = ""
        let adresse = ""
        let code = ""
        let ville = ""


        if (item){
            if (item.housenumber){
               numero = item.housenumber

                if (item.street){
                    adresse = item.street

                }
            }
            else{
                adresse = item.name

            }
            if (item.postcode){
                code = item.postcode

            }
            if (item.city){
                ville = item.city

            }


        }
        $('#in_numero_cc').val(numero).trigger('updateAddress')
        $( '#in_cp_cc' ).val(code).trigger('updateAddress')
        $( '#in_ville_cc' ).val(ville).trigger('updateAddress')
        $( '#in_adresse_cc' ).val(adresse).trigger('updateAddress')
        $('#in_adr_srch').on('click').val('')
    });
    // suppression de la  demande client
    $('body').on('click touchstart','.clotureDemande',(e)=>{
        let demandeId = $(e.currentTarget).attr('id').split("_")[1]


        if(confirm("Etes-vous certain de vouloir clôre la demande pour etre sur ?")){
            closeDemande(demandeId)
        }
    })
    // suppression des rdv client
    $('body').on('click touchstart','.clotureRdv',(e)=>{
        let rdvId = $(e.currentTarget).attr('id').split("_")[1]


        if(confirm("Etes-vous certain de vouloir supprimer le RDV ?")){
            closeRdv(rdvId)
        }
    })
    // update de la  justification client (select)
    $(document).on('change','#se_justification',(e)=>{
        let justif_id = (($(e.currentTarget).val() == 0)? '@#!?' : $(e.currentTarget).val())
        let demande_id = $(e.currentTarget).attr('data-demandeid')
        $.post("./ajax/client_complete.php",{
            a:'update_justification',
            demande_id:demande_id,
            justif_id:justif_id
        },(data)=>{
            getAsking()
        },"json")
    })
    // update de la  justification client (text area)
    $(document).on('keyup','#ta_infos_supp',(e)=>{
        let info = (($(e.currentTarget).val() == '')? '@#!?' : $(e.currentTarget).val())
        let demande_id = $(e.currentTarget).attr('data-demandeid')

        if(saferequest !== null){
            clearTimeout(saferequest)
        }

        saferequest = setTimeout(()=>{
            $.post("./ajax/client_complete.php",{
                a:'update_info',
                demande_id:demande_id,
                info:info
            },(data)=>{
                getAsking()
            },"json")
        }, 500)
    })
    //envoie de sms pour report de rdv
    $(document).on('click','#btn-sms-report',(e)=>{
        disabledButton(e.currentTarget, 3000, false, true);
        let receiver = $(e.currentTarget).attr('data-clientphone')
        let heure = $(e.currentTarget).attr('data-heure')
        let prenom = $(e.currentTarget).attr('data-clientprenom')
        let consultant = $(e.currentTarget).attr('data-consname')
        send_sms_report(receiver,heure,prenom,consultant)
    })
    //envoie de sms pour confirmation de rdv
    $(document).on('click','#btn-sms-confirm',(e)=>{
        disabledButton(e.currentTarget, 3000, false, true);
        let receiver = $(e.currentTarget).attr('data-clientphone')
        let heure = $(e.currentTarget).attr('data-heure')
        let date_rdv = $(e.currentTarget).attr('data-date')
        let prenom = $(e.currentTarget).attr('data-clientprenom')
        let consultant = $(e.currentTarget).attr('data-consname')
        send_sms_confirm(receiver,date_rdv,heure,prenom,consultant)
    })


}

// affichage du chart score client
function loadChart(text = 'light'){

    am4core.ready(function() {
        am4core.options.autoSetClassName = true;
// Themes begin
        // am4core.useTheme(am4themes_kelly);
// Themes end

// create chart

        var chart = am4core.create("score_graph", am4charts.GaugeChart);
        chart.hiddenState.properties.opacity = 0; // this makes initial fade in effect
        chart.align = 'center'
        chart.innerRadius = -30;
        chart.height = 170;
        chart.width = 230;
        let title = chart.titles.create();
        title.text = "Score Client";
        title.fontSize = 20;
        title.marginBottom = 0;
        title.marginTop = -15;






        var axis = chart.xAxes.push(new am4charts.ValueAxis());
        axis.min = 0;
        axis.max = 100;
        axis.strictMinMax = true;
        axis.renderer.grid.template.stroke = am4core.color("#004dff");


        axis.renderer.grid.template.strokeOpacity = 0;
        axis.renderer.labels.template.disabled = true;

        var range0 = axis.axisRanges.create();
        range0.value = 0;
        range0.endValue = 25;
        range0.axisFill.fillOpacity = 1;
        range0.axisFill.fill = am4core.color("#ff0800");
        range0.axisFill.zIndex = - 1;

        var range1 = axis.axisRanges.create();
        range1.value = 25;
        range1.endValue = 50;
        range1.axisFill.fillOpacity = 1;
        range1.axisFill.fill = am4core.color("#ff9700");
        range1.axisFill.zIndex = -1;

        var range2 = axis.axisRanges.create();
        range2.value = 50;
        range2.endValue = 75;
        range2.axisFill.fillOpacity = 1;
        range2.axisFill.fill = am4core.color("#ede300");


        range2.axisFill.zIndex = -1;

        var range3 = axis.axisRanges.create();
        range3.value = 75;
        range3.endValue = 100;
        range3.axisFill.fillOpacity = 1;
        range3.axisFill.fill = am4core.color("#00b50d");
        range3.axisFill.zIndex = -1;

        var hand = chart.hands.push(new am4charts.ClockHand());

// using chart.setTimeout method as the timeout will be disposed together with a chart
        hand.showValue(76, 100, am4core.ease.cubicOut);

        if (text === 'dark'){
            hand.fill = am4core.color("#000");
            hand.stroke = am4core.color("#c9c9c9");
            title.fill = am4core.color("#c9c9c9");
            range3.axisFill.fill = am4core.color("#6e846c");
            range3.axisFill.stroke = am4core.color("#71c668");
            range2.axisFill.fill = am4core.color("#bec153");
            range2.axisFill.stroke = am4core.color("#e9ed52");
            range1.axisFill.fill = am4core.color("#c9944d");
            range1.axisFill.stroke = am4core.color("#eeaf5e");
            range0.axisFill.fill = am4core.color("#ba504a");
            range0.axisFill.stroke = am4core.color("#ec6c63");
        }

        //chart.setTimeout(randomValue, 2000);

        //function randomValue() {
            //hand.showValue(Math.random() * 100, 1000, am4core.ease.cubicOut);
            //chart.setTimeout(randomValue, 2000);
        //}

    }); // end am4core.ready()
}
// Tableau Historique de consultation
function getTableDataHisto(data_histo) {
       table1 = $("#tableHistoConsult").DataTable({
            data: data_histo,
            dataType: "json",
            order: [0,'desc'],



            columns: [
                {"title": "#ID", "data": "id"},
                {
                    "title": "Date",
                    "data": {
                        _: "date",
                        sort: "timestamp"
                    }
                },
                {"title": "Statut", "data": "statut"},
                {"title": "Durée", "data": "duree"},
                {"title": "Consultant", "data": "cons_id"},
                {"title": "Cabinet", "data": "cabinet_id"},
                {"title": "Service", "data": "type_service"},
                {"title": "Paiement", "data": "statut_paiement"},
                {"title": "Coût/Promo", "data": "type_paiement"},

            ],
           fnDrawCallback:function() {
               $("input[type='search']").addClass("modifyColorSearch");
           },
           columnDefs: [

               {
                   targets: 2,
                   createdCell: function(td, cellData, rowData, row, col)
                   {
                    switch (data_histo.statut){
                        case '1':
                            $(td).html('En cours')
                            break;
                        case '2':
                            $(td).html('xxxx')
                            break;
                        default:
                            $(td).html('Terminé')
                            break;
                    }

                   }
               },
               {
                   targets: 4,
                   createdCell: function(td, cellData, rowData, row, col)
                   {

                       $(td).html('<div>' + rowData.cons_pseudo +'<br>('+rowData.cons_id + ')</div>')


                   }
               },
               {
                   targets: 5,
                   createdCell: function(td, cellData, rowData, row, col)
                   {

                       $(td).html('<div>' + rowData.cabinet +'<br>('+rowData.cabinet_id + ')</div>')


                   }
               },
               {
                   targets: 6,
                   createdCell: function(td, cellData, rowData, row, col)
                   {

                       switch (data_histo.type_service){
                           case '1':
                               $(td).html('Audiotel')
                               break;
                           case '2':
                               $(td).html('TPE')
                               break;
                           case '2':
                               $(td).html('Web')
                               break;
                           default:
                               $(td).html('Secrétariat')
                               break;
                       }


                   }
               },
               {
                   targets: 7,
                   createdCell: function(td, cellData, rowData, row, col)
                   {

                       switch (rowData.statut_paiement){

                           case '1':
                               text = '<div class="col "><div>Partiel</div><div><i class="fal fa-meh"></i></div></div>'
                               couleur = 'statistic__item--orange'
                               break;
                           case '2':
                               text = '<div class="col "><div>Payé</div><div><i class="fal fa-smile"></i></div></div>'
                               couleur = 'statistic__item--green'
                               break;
                           default:
                               text = '<div class="col "><div>En attente</div><div><i class="fal fa-frown"></i></div></div>'
                               couleur = 'statistic__item--red'
                               break;
                       }

                       if ((rowData.duree_calcul <= 160)||( rowData.interdit_dette == true)){
                           main = '<div><i class="fal fa-hand-paper"></i></div>'
                           corner = ''
                           couleurManuel = 'statistic__item--blue'
                       }
                        else{
                           main = ''
                           corner = 'manuel_top_left_corner'
                           couleurManuel = 'statistic__item--gris'
                       }

                       if (rowData.date_cb_diff != 0){
                           diff = '<div class=" manuel_bottom_left_corner" ><div><strong>Cb diff:</strong></div><div>'+rowData.date_cb_diff+'</div></div>'
                       }
                       else{
                           diff ='<div class=" manuel_bottom_left_corner" ><div><strong>Cb diff:</strong></div><div>N/A</div></div>'
                       }


                       switch (rowData.type_paiement){
                           case '2':
                               biscotte = 'Chèque'
                               break;
                           case '3':
                               biscotte = 'Virement'
                               break;
                           case '4':
                               biscotte = 'Compensation'
                               break;
                           case '5':
                               biscotte = 'CB diff'
                               break;
                           default:
                               biscotte = 'CB'
                               break;
                       }
                       payeEn = '<div class="col '+corner+' ">' +
                                       '<div>' +
                                                '<strong>Payé en:</strong>' +
                                       '</div>' +
                                       '<div class="row d-flex justify-content-center align-items-center">' +
                                                    '<div class="mr-2">'+biscotte+'</div>'
                                                    +main+
                                       '</div>' +
                                '</div>'

                       let aff = ''
                       aff += '<div class="fond_badge row manuel_total_corner w-100 '+couleur+'">'
                       aff += '     <div class="statut col-6 p-0 d-flex justify-content-center align-items-center">'
                       aff += '         '+text+''
                       aff += '     </div>'
                       aff += '     <div class="'+couleurManuel+' date_diff col-6 pr-0 pl-0 manuel_top_and_bottom_right_corner ">'
                       aff += '         <div class="'+corner+'">'+payeEn+'</div>'
                       aff += '         '+diff+''
                       aff += '     </div>'
                       aff += '</div>'

                       $(td).addClass("d-flex justify-content-center align-items-center")
                       $(td).html(aff)
                   }

               },
               {
                   targets: 8,
                   createdCell: function (td, cellData, rowData, row, col) {
                       if(rowData.reste > 0){
                           sirene = "sec-new-title-supervisor"
                       }
                       else{
                           sirene=''
                       }


                       let aff = ''

                       aff += '<div class="badge_promo col-12 d-flex manuel_total_corner w-100 statistic__item--gris">'
                       aff += '     <div class="col-6 p-0 ">'
                       aff += '         <div><strong>Total:</strong><i class="ml-2 fad fa-euro-sign"></i></div>'
                       aff += '         <div>' + rowData.montant + '€</div>'
                       aff += '         <div><strong>Montant consult:</strong><i class="ml-2 fad fa-piggy-bank"></i></div>'
                       aff += '         <div>' + rowData.montant_du + '€</div>'
                       aff += '     </div>'
                       aff += '     <div class="col-6 pr-0 pl-0 ">'
                       aff += '         <div><strong>Code promo:</strong><i class="ml-2 fad fa-badge-percent"></i></div>'
                       aff += '         <div>' + rowData.code_promo_pourcentage + ' </div>'
                       aff += '         <div><strong>Reste:</strong><i class=" '+sirene+' ml-2 fad fa-siren-on"></i> </div>'
                       aff += '         <div>' + rowData.reste + '€</div>'
                       aff += '     </div>'
                       aff += '</div>'


                       //$(td).addClass("d-flex justify-content-center align-items-center")
                       $(td).html(aff)



                       //$(td).prev().find('.fond_badge').height()

                   },

               }
           ],


        })


}
// Tableau des RDV client
function getTableDataRDV(rdv) {

    table2 = $("#tableRDVClient").DataTable({
        data: rdv,
        dataType: "json",

        columns: [
            {"title": "ID", "data": "id"},
            {"title": "Date", "data": "date"},
            {"title": "Heure", "data": "heure"},
            {"title": "Consultant", "data": "consultant_id"},
            {"title": "Téléconseiller", "data": "telecons_id"},
            {"title": "SMS", "data": "icon_sms"},
            {"title": "Options", "data": "option"}

        ],
        fnDrawCallback:function() {
            $("input[type='search']").addClass("modifyMargin120");
            $("input[type='search']").addClass("modifyColorSearch");
        },


        "columnDefs": [
            {
                "targets": 3,
                "createdCell": function (td, cellData, rowData, row, col) {
                    $(td).html('<div>' + rowData.consultant_name + '<br>(' + rowData.consultant_id + ')</div>')
                }
                },
            {
                "targets": 4,
                "createdCell": function (td, cellData, rowData, row, col) {
                    $(td).html('<div>' + rowData.telecons_name + '<br>(' + rowData.telecons_id + ')</div>')
                }
            },
            {
                "targets": 5,
                "createdCell": function (td, cellData, rowData, row, col) {
                    aff = "<button class='btn btn-warning p-1' id='btn-sms-report' data-heure='"+rowData.heure_complete+"' data-clientphone='"+rowData.mobile+"' data-clientprenom='"+rowData.client_prenom+"' data-clientid='"+cellData+"' data-consname='"+rowData.cons_name+"' data-consid='"+rowData.cons_id+"'"+((rowData.mobile)?'':'disabled')+">"+
                        "<i class=\"fas fa-sms fa-2x\"></i>"+
                        "</button>"+"<button class='btn btn-success p-1' id='btn-sms-confirm' data-date='"+rowData.date_rdv+"' data-heure='"+rowData.heure_complete+"' data-clientphone='"+rowData.mobile+"' data-clientprenom='"+rowData.client_prenom+"' data-clientid='"+cellData+"' data-consname='"+rowData.cons_name+"' data-consid='"+rowData.cons_id+"'"+((rowData.mobile)?'':'disabled')+">"+
                        "<i class=\"fas fa-sms fa-2x\"></i>"+
                        "</button>"
                    $(td).html(aff)

                }
            },{
                "targets": 6,
                "createdCell": function (td, cellData, rowData, row, col) {
                    $(td).html ("<div><span class='clotureRdv' id='close_"+rowData.id+"'><i class=\"fas fa-trash-alt pr-3\"></i>Supprimer RDV</span></div>")

                }
            },


        ]
    })
}
// Tableau des demandes client
function getTableDataDemandes(demandes) {

    table3 = $("#tableDemandeClient").DataTable({
        data: demandes,
        dataType: "json",

        columns: [
            {"title": "#ID", "data": "id"},
            {
                "title": "Date",
                "data": {
                    _: "date",
                    sort: "timestamp"
                }
            },
            {"title": "Demande", "data": "consultant_name"},
            {"title": "Justification", "data": "justification"},
            {"title": "MaJ Justification", "data": "maj_justification"},
            {"title": "Options", "data": "option"}

        ],
        fnDrawCallback:function() {
            $("input[type='search']").addClass("modifyMargin120");
        },
        columnDefs: [
            {
                "targets": 2,
                "createdCell": function (td, cellData, rowData, row, col, data) {
                    $(td).html('<div>' + rowData.consultant_name +'<br>('+rowData.consultant_id + ')</div>')

                    }



            },
            {
                "targets":3,
                "createdCell": function (td, cellData, rowData, row, col) {

                    if(rowData.info_demande === null){
                        infos_demandes = ""
                    }
                    else{
                        infos_demandes = rowData.info_demande
                    }

                    let aff = ''
                    let setOpts = justif_demande_client_to_select(rowData.justification)
                    aff += '<div class="d-flex flex-column">'
                    aff += '    <div class="badge badge-grey d-flex" style="height:60px;width:100%;">'
                    aff += '        <i class="fal fa-sticky-note mr-2"></i>'
                    aff += '        <select style="width:80%;" class="custom-select" id="se_justification" data-demandeid="'+rowData.id+'">'
                    aff +=              setOpts
                    aff += '        </select>'
                    aff += '    </div>'
                    aff += '        <div class="badge badge-grey d-flex mt-2" style="height:60px;width:100%;">'
                    aff += '        <i class="fas fa-info-circle mr-2"></i>'
                    aff += '        <textarea style="width:80%;" id="ta_infos_supp" data-demandeid="'+rowData.id+'">'+infos_demandes+'</textarea>'
                    aff += '    </div>'
                    aff += '</div>'
                    $(td).addClass("d-flex justify-content-center align-items-center")
                    $(td).html(aff)

                }
            },
            {
                "targets":5,
                "createdCell": function (td, cellData, rowData, row, col) {
                    $(td).html ("<div><span class='clotureDemande' id='close_"+rowData.id+"'><i class=\"fas fa-clipboard-check pr-3\"></i>Clore la demande</span></div>")

                }
            },



        ]


    })
}
// affichage des informations générales dans la vue de la page client
function gen_infos_client(data_infos) {
    $('#se_genre_cc').val(data_infos.genre)
    $('#in_nom_cc').val(data_infos.nom)
    $('#in_prenom_cc').val(data_infos.prenom)
    $('#in_email_cc').val(data_infos.mail)
    if (data_infos.ddn){
        $('#in_ddn_cc').val(convertDate(data_infos.ddn))
    }
    $('#in_infos_client_cc').text(data_infos.infos_client)
    if (!null){
        $('#se_pays_cc').val(data_infos.pays)
    }
    else{
        $('#se_pays_cc').text('France')
    }
    $('#in_cp_cc').val(data_infos.cp)
    $('#in_ville_cc').val(data_infos.ville)
    $('#in_adresse_cc').val(data_infos.adr1)
    $('#in_numero_cc').val(data_infos.adr2)
    if (data_infos.vip == 1 ){
        $('#check_isVip_cc').attr("checked", true);
        }
        else {
        $('#check_isVip_cc').attr("checked", false);
    }
    if (data_infos.sms_cons == 1 ){
        $('#check_confirmSms_cc').attr("checked", true);
    }
    else {
        $('#check_confirmSms_cc').attr("checked", false);
    }
    if (data_infos.sms_anniv == 1 ){
        $('#check_smsAnniv_cc').attr("checked", true);
    }
    else {
        $('#check_smsAnniv_cc').attr("checked", false);
    }
    if (data_infos.interdit == 1 ){
        $('#check_isForbidden_cc').attr("checked", true);
    }
    else {
        $('#check_isForbidden_cc').attr("checked", false);
    }
    if (data_infos.dette == 1 ){
        $('#check_isForbiddenDebt_cc').attr("checked", true);
    }
    else {
        $('#check_isForbiddenDebt_cc').attr("checked", false);
    }
    if (data_infos.interdit_dette == 1 ){
        $('#check_isForbiddenDebt_cc').attr("checked", true);
    }
    else {
        $('#check_isForbiddenDebt_cc').attr("checked", false);
    }
    if (data_infos.coupure == 1 ){
        $('#check_limit_tpe_cc').attr("checked", true);
    }
    else {
        $('#check_limit_tpe_cc').attr("checked", false);
    }
    if (data_infos.blacklist == 1 ){
        $('#check_isBlacklist_cc').attr("checked", true);
    }
    else {
        $('#check_isBlacklist_cc').attr("checked", false);
    }
    //$('#in_telephone_cc').val(data_infos.phone)


}
// affichage des stats  dans la vue de la page client
function gen_stats_client(data_infos){
    $('#text_infos_consultant1_nom_cc').html(data_infos.consultant_pseudo_1)
    $('#text_infos_consultant1_id_cc').html(data_infos.consultant_id_1 )
    $('#text_infos_consultant2_nom_cc').html(data_infos.consultant_pseudo_2)
    $('#text_infos_consultant2_id_cc').html(data_infos.consultant_id_2)
    $('#text_infos_total_cc').html(data_infos.first)
    $('#text_infos_cb_cc').text(data_infos.taux_cons_cb+ " %")
    $('#text_infos_auto_cc').text(data_infos.taux_cons_auto+ " %")
    $('#text_infos_retard_cc').html(data_infos.retard + " €")
    $('#text_infos_impayes_cc').html(data_infos.impaye)
    $('#text_infos_panier_cc').html(data_infos.panier + " €")
    $('#text_infos_solde_cc').html(data_infos.solde + " €")
    $('#text_infos_consult_cb_cc').html(data_infos.consult_cb)
    $('#text_infos_consult_auto_cc').html(data_infos.consult_auto)
    $('#text_infos_consult_web_cc').html(data_infos.consult_web)



}
// affichage des infos téléphones  dans la vue de la page client
function gen_phone_client(data_infos){

    $(data_infos).each(function(i,e) {

        let domicile="",bureau="",autre="",non="",mobile="",boutton ="",bouttonID=""

        switch (e.type){

            case '1':
                mobile = 'selected="selected"'
                break;
            case '2':
                domicile = 'selected="selected"'
                break;
            case '3':
                bureau = 'selected="selected"'
                break;
            case '4':
                autre = 'selected="selected"'
                break;
            default :
                non = 'selected="selected"'
                break;

        }
        switch (e.principal){

            case '1':
                boutton = 'btn-blue'
                bouttonID = "btn-star"
                break;

            default :
                boutton = 'btn-outline-blue'
                bouttonID = "btn-star-outline"
                break;

        }

        //console.log(e)
        let aff= ""
        aff+= '<div class="d-flex align-items-stretch fc_phone_item" data-db="clientPhone"'
        aff+=       'data-phone-id="'+e.phones_id+'"'
        aff+=       'data-phone-num="'+e.numero+'">'
        aff+=       '<button id ="'+bouttonID+'" type="button" class="btn btn-sm '+boutton+'"><i class="fas fa-star fa-fw"></i>'
        aff+=       '</button>'
        aff+=       '<div class="flex-grow-1 text-center my-1 d-flex flex-row justify-content-around align-items-center flex-wrap">'
        aff+=         '<div class="mb-0">'
        aff+=            '<input class="form-control" type="text" id="in_telephone_cc" value="'+e.numero+'"/>'
        aff+=         '</div>'
        aff+=         '<select class="custom-select custom-select-xs border-0 shadow-0 w-auto"'
        aff+=                  'aria-label="Type"'
        aff+=                  'id="inputGroupSelect01">'
        aff+=              '<option value="0" '+non+' >Non vérifié</option>'
        aff+=              '<option value="1" '+mobile+' >Mobile</option>'
        aff+=              '<option value="2" '+domicile+' >Domicile</option>'
        aff+=              '<option value="3" '+bureau+' >Bureau</option>'
        aff+=              '<option value="4" '+autre+' >Autre</option>'
        aff+=          '</select></div>'
        aff+= '<button type="button" class="btn btn-sm btn-outline-success verif_item">'
        aff+=          '<i class="fas fa-check fa-fw"></i></button>'
        aff+= '<button type="button" class="btn btn-sm btn-outline-success call_item">'
        aff+=           '<i class="fas fa-phone-volume fa-fw"></i></button>'
        aff+= '<button type="button" class="btn btn-sm btn-outline-danger delete_item">'
        aff+=           '<i class="far fa-trash-alt fa-fw"></i></button>'
        aff+= '</div>'
        $( '#in_infos_numero_telephone_cc' ).append( aff );
    });
}
// affichage des infos cb  dans la vue de la page client
function gen_card_client(data_infos){
    $(data_infos).each(function(i,e){
        let card = generateCard2(e.id,e.last_number,e.owner,e.exp_month,e.exp_year,"small",e.type,e.alias,e.is_delete,e.cvc)
        $('.fakeCard').append(card)
        $(".fakeCard").css("z-index","0")

    })



}
//Envoi Ajax pour la mise a jours des infos
function setData_updateElementForm(client_id, element, value){

    if(value !== false){

        $.ajax({
            type: 'POST',
            url: './ajax/client_complete.php',
            data: {
                "a": "setData_updateElementForm",
                "client_id": client_id,
                "element": element,
                "value": value
            },
            dataType: 'json',
            success: function (data, statut, xhr) {
                //console.log(data);
                if(!data.erreur){
                    if(element == 'nom'){
                        if($('#in_prenom_cc').hasClass('is-require')){
                            isDangerCustom($('#in_prenom_cc'));
                            socket.emit("message", "refresh");
                        }
                    }
                    if(element == 'prenom'){
                        if($('#in_nom_cc').hasClass('is-require')) {
                            isDangerCustom($('#in_nom_cc'));
                            socket.emit("message", "refresh");
                        }
                    }
                }
            }
        });
    }
}
//Envoi ajax pour suppression des demandes
function closeDemande(demandeId){

    $.post("./ajax/client_complete.php",{
        a:'closeDemande',
        id:client.id,
        demandeId:demandeId
    },(data)=>{
        if(!data.error){
            if (table3){
                table3.destroy()
            }
            getTableDataDemandes(data.demandes)
        }
    },'json')
}
//Envoi ajax pour suppression des rdv
function closeRdv(rdvId){

    $.post("./ajax/client_complete.php",{
        a:'closeRdv',
        id:client.id,
        rdvId:rdvId
    },(data)=>{
        if(!data.error){
            if (table2){
                table2.destroy()
            }
            getTableDataRDV(data.rdv)
        }
    },'json')
}
//Envoi ajax pour report de rdv pas sms
function send_sms_report(receiver,heure,prenom,consultant){
    $.post("./ajax/client_complete.php",{
        a:'send_sms_report',
        r:receiver,
        h:heure,
        p:prenom,
        c:consultant
    },(data)=>{
        if(!data.error){
            console.log('envoi OK')
        }else{
            console.log(data.error_str)
        }
    },"json")
}
//Envoi ajax pour confirmation de rdv pas sms
function send_sms_confirm(receiver,date_rdv,heure,prenom,consultant){
    $.post("./ajax/client_complete.php",{
        a:'send_sms_confirm',
        r:receiver,
        d:date_rdv,
        h:heure,
        p:prenom,
        c:consultant
    },(data)=>{
        if(!data.error){
            console.log('envoi OK')
        }else{
            console.log(data.error_str)
        }
    },"json")
}