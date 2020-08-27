<?php

class AFSAConfigFormAutoTrack extends AFSAConfigForm
{
    private $switch_label;

    public function __construct($module)
    {
        $this->form_id = 'AutoTrack';

        parent::__construct($module);

        $this->fields = array(
            'afs_analytics_autotrack_all',
            'afs_analytics_autotrack_outboundclick',
            'afs_analytics_autotrack_insideclick',
            'afs_analytics_autotrack_video',
                /* 'afs_analytics_autotrack_download',
                  'afs_analytics_autotrack_video',
                  'afs_analytics_autotrack_iframe' */
        );

        $this->switch_label = array(
            'afs_analytics_autotrack_all' => $this->l('AutoTrack Default'),
            'afs_analytics_autotrack_outboundclick' => $this->l('Outbound clicks tracking'),
            'afs_analytics_autotrack_insideclick' => $this->l('Inside clicks tracking'),
            'afs_analytics_autotrack_download' => $this->l('Download tracking'),
            'afs_analytics_autotrack_video' => $this->l('Video tracking'),
            'afs_analytics_autotrack_iframe' => $this->l('Iframe tracking'),
        );
    }

    public function install()
    {
        Configuration::updateValue('AFS_ANALYTICS_AUTOTRACK_ALL', 1);
        Configuration::updateValue('AFS_ANALYTICS_AUTOTRACK_OUTBOUNDCLICK', 1);
        Configuration::updateValue('AFS_ANALYTICS_AUTOTRACK_INSIDECLICK', 0);
        Configuration::updateValue('AFS_ANALYTICS_AUTOTRACK_DOWNLOAD', 0);
        Configuration::updateValue('AFS_ANALYTICS_AUTOTRACK_VIDEO', 0);
        Configuration::updateValue('AFS_ANALYTICS_AUTOTRACK_IFRAME', 0);
    }

    /*
     *
     *
     *
     */

    public function getFieldsData()
    {
        $ret = array();

        $radioOptions = array(
            array(
                'id' => 'active_on',
                'value' => 1,
                'label' => $this->l('Enabled'),
            ),
            array(
                'id' => 'active_off',
                'value' => 0,
                'label' => $this->l('Disabled'),
        ), );

        $fields = array();
        foreach ($this->fields as $k) {
            switch ($k) {
                case 'afs_analytics_autotrack_all':
                    $fields[] = array(
                        'type' => 'switch',
                        'label' => $this->switch_label[$k],
                        'name' => $k,
                        'values' => $radioOptions,
                    );

                    break;

                default:
                    $fields[] = array(
                        'type' => 'select',
                        'label' => $this->switch_label[$k],
                        'name' => $k,
                        'options' => array(
                            'query' => array(
                                array('key' => 1, 'name' => 'On'),
                                array('key' => 0, 'name' => 'Dataset'),
                                array('key' => 2, 'name' => 'Off'),
                            ),
                            'id' => 'key',
                            'name' => 'name',
                        ),
                    );
                    break;
            }
        }

        $ret[]['form'] = array(
            'legend' => array(
                'title' => $this->l('Monitored Events'),
            ),
            'input' => $fields,
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ),
        );

        return $ret;
    }
}
