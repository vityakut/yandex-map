<?php
/**
 * Created by PhpStorm.
 * User: phpNT - http://phpnt.com
 * Date: 28.04.2017
 * Time: 8:00
 */

namespace vityakut\yandexMap;

use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class YandexMaps extends Widget
{
    public $myPlacemarks;
    public $myRoute;
    public $mapOptions;
    public $additionalOptions = ['searchControlProvider' => 'yandex#search'];

    public $disableScroll   = true;

    public $windowWidth = '100%';
    public $windowHeight = '400px';

    public function init()    {
        parent::init();
        $this->myPlacemarks = ($this->myPlacemarks) ? ArrayHelper::toArray($this->myPlacemarks) : [];
        $this->myRoute = ($this->myRoute) ? ArrayHelper::toArray($this->myRoute) : [];
        $this->mapOptions = Json::encode($this->mapOptions);
        $this->additionalOptions = Json::encode($this->additionalOptions);
        $this->disableScroll = $this->disableScroll ? 1 : 0;
        $this->registerClientScript();
    }

    public function run()
    {

        //dd($this->id);
        return $this->render(
            'view',
            [
                'widget' => $this
            ]);
    }

    public function registerClientScript()
    {
        $countPlaces = count($this->myPlacemarks);
        $countRoute = count($this->myRoute);
        $items  = [];
        $i      = 0;
        if ($countPlaces > 0){
            foreach ($this->myPlacemarks as $one) {
                $items[$i]['latitude']  = $one['latitude'];
                $items[$i]['longitude'] = $one['longitude'];
                $items[$i]['options'] = $one['options'];
                $i++;
            }
        }
        $myPlacemarks = json_encode($items);
        $myRoute = json_encode($this->myRoute);
        $view = $this->getView();

        YandexMapsAsset::register($view);
// TODO: replace options by init
        $myMap = "window.myMap_".$this->id;
        $js = <<< JS
        ymaps.ready(init$this->id);
            var myRoute, myPlacemark;
        
            function init$this->id(){
                $myMap = new ymaps.Map("$this->id", {$this->mapOptions}, {$this->additionalOptions});
                
                var disableScroll = $this->disableScroll;
                if ($this->disableScroll) {
                    $myMap.behaviors.disable('scrollZoom');                    
                }

                var myPlacemarks = $myPlacemarks;        
        
                if (myPlacemarks.length){
                    for (var i = 0; i < $countPlaces; i++) {
                        myPlacemark = new ymaps.Placemark([myPlacemarks[i]['latitude'], myPlacemarks[i]['longitude']],
                            myPlacemarks[i]['options'][0],
                            myPlacemarks[i]['options'][1],
                            myPlacemarks[i]['options'][2],
                            myPlacemarks[i]['options'][3],
                            myPlacemarks[i]['options'][4],
                            myPlacemarks[i]['options'][5]
                        );
                    
                        $myMap.geoObjects.add(myPlacemark);
                    }
                }
                var myRoute = $myRoute;
                if (myRoute.length){
                    ymaps.route(myRoute)
                        .done(function (route) {
                            route.options.set("mapStateAutoApply", true);
                            route.getPaths().options.set({
                                'strokeColor': 'FFB801',
                                'opacity': '0.5'
                            });
                            route.getWayPoints().options.set({
                                'preset' : 'islands#yellowMassTransitIcon',
                                'iconColor' : '#FFB801'
                            });
                            route.getViaPoints().options.set({
                                'iconColor' : '#FFB801',
                                'preset': 'islands#yellowCircleDotIcon'
                               
                            });
                            $myMap.geoObjects.add(route);
                        }, function (err) {
                            throw err;
                        }, this);
                }
                
                var location = ymaps.geolocation.get();
                location.then(
                    function(result) {
                    $myMap.geoObjects.add(result.geoObjects)
                });
//                if ($myMap.geoObjects.getBounds()){
//                    $myMap.setBounds($myMap.geoObjects.getBounds(), {checkZoomRange:true});
                    // $myMap.setZoom($myMap.getZoom()-0.4);
                // }
            }
JS;
        $view->registerJs($js);
    }
}
