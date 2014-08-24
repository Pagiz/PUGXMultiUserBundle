<?php

namespace PUGX\MultiUserBundle\Model;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use \Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Description of UserDiscriminator
 * 
 * @author leonardo proietti (leonardo.proietti@gmail.com)
 * @author eux (eugenio@netmeans.net)
 */
class UserDiscriminator extends ContainerAware
{
    const SESSION_NAME = 'pugx_user.user_discriminator.class'; 
    
    /**
     *
     * @var SessionInterface 
     */
    protected $session;
    
    /**
     *
     * @var array 
     */
    protected $conf = array();
    
    /**
     *
     * @var Symfony\Component\Form\Form 
     */
    protected $registrationForm = null;
    
    /**
     *
     * @var Symfony\Component\Form\Form 
     */
    protected $profileForm = null;
    
    /**
     *
     * @var string 
     */
    protected $class = null;
    
    /**
     * Current form
     * @var type 
     */
    protected $form = null;

    /**
     *
     * @param SessionInterface $session
     * @param array $parameters 
     */
    public function __construct(SessionInterface $session, array $parameters)
    {
        $this->session = $session;        
        $this->buildConfig($parameters);
    }
    
    /**
     *
     * @return array 
     */
    public function getClasses()
    {        
        $classes = array();
        foreach ($this->conf as $entity => $conf) {
            $classes[] = $entity;
        }
        
        return $classes;
    }
        
    /**
     *
     * @param string $class 
     */
    public function setClass($class, $persist = false)
    {
        if (!in_array($class, $this->getClasses())) {
            throw new \LogicException(sprintf('Impossible to set the class discriminator, because the class "%s" is not present in the entities list', $class));
        }
        
        if ($persist) {
            $this->session->set(static::SESSION_NAME, $class);
        }
        
        $this->class = $class;
    }
    
    /**
     *
     * @return string 
     */
    public function getClass()
    {       
        if (!is_null($this->class)) {
            return $this->class;
        }
        
        $storedClass = $this->session->get(static::SESSION_NAME, null);

        if ($storedClass) {
            $this->class = $storedClass;
        }
        
        if (is_null($this->class)) {
            $entities = $this->getClasses();
            $this->class = $entities[0];
        }
        
        return $this->class;
    }
    
    /**
     *
     * @return type 
     */
    public function createUser()
    {
        $factory = $this->getUserFactory();
        $user    = $factory::build($this->getClass());
        
        return $user;
    }
    
    /**
     * 
     * @return string
     */
    public function getUserFactory()
    {
        return $this->conf[$this->getClass()]['factory'];
    }
    
    /**
     * 
     * @param string $name
     * @return 
     * @throws \InvalidArgumentException
     */
    public function getFormType($name)
    {
        $class = $this->getClass();
        $formtype = $this->conf[$class][$name]['form']['type'];
        
        if (class_exists($formtype)) 
        {
            $type = new $formtype($class);
            
        }
        elseif ($this->container->has($formtype))
        {
            $type = $this->container->get($formtype);
        }
        else 
        {
            throw new \InvalidArgumentException(sprintf('UserDiscriminator, error getting form type : "%s" not found', $formtype));
        }
        
        
        return $type;
    }
    
    /**
     * 
     * @param string $name
     * @return string
     */
    public function getFormName($name)
    {
        return $this->conf[$this->getClass()][$name]['form']['name'];
    }
    
    /**
     * 
     * @param type $name
     * @return type
     */
    public function getFormValidationGroups($name)
    {
        return $this->conf[$this->getClass()][$name]['form']['validation_groups'];
    }

    /**
     * 
     * @return string
     */
    public function getTemplate($name)
    {
        return $this->conf[$this->getClass()][$name]['template'];
    }
    
    /**
     *
     * @param array $entities
     * @param array $registrationForms
     * @param array $profileForms 
     */
    protected function buildConfig(array $users)
    {
        foreach ($users as $user) {
            
            $class = $user['entity']['class'];
            
            if (!class_exists($class)) {
                throw new \InvalidArgumentException(sprintf('UserDiscriminator, configuration error : "%s" not found', $class));
            }
            
            $this->conf[$class] = array(
                    'factory' => $user['entity']['factory'],
                    'registration' => array(
                        'form' => array(
                            'type' => $user['registration']['form']['type'],
                            'name' => $user['registration']['form']['name'],
                            'validation_groups' => $user['registration']['form']['validation_groups'],
                        ),                        
                        'template' => $user['registration']['template'],
                    ),
                    'profile' => array(
                        'form' => array(
                            'type' => $user['profile']['form']['type'],
                            'name' => $user['profile']['form']['name'],
                            'validation_groups' => $user['profile']['form']['validation_groups'],
                        ),
                        'template' => $user['profile']['template'],
                    ),
                    'changePassword' => array(
                        'form' => array(
                            'type' => $user['changePassword']['form']['type'],
                            'name' => $user['changePassword']['form']['name'],
                            'validation_groups' => $user['changePassword']['form']['validation_groups'],
                        ),
                        'template' => $user['changePassword']['template'],
                    ),
                    'resetting' => array(
                        'form' => array(
                            'type' => $user['resetting']['form']['type'],
                            'name' => $user['resetting']['form']['name'],
                        ),
                        'template' => $user['resetting']['template'],
                    ),
                    'login' => array(
                        'form' => array(
                            'type' => $user['login']['form']['type'],
                            'name' => $user['login']['form']['name'],
                        ),
                        'template' => $user['login']['template'],
                    )
                );
        }
    }
}
