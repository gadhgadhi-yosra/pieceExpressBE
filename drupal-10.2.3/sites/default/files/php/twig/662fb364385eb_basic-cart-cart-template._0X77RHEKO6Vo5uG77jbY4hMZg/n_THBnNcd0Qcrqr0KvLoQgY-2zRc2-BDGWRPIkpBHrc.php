<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* modules/basic_cart/templates/basic-cart-cart-template.html.twig */
class __TwigTemplate_b2d742ea394353dac35b8689399d79c5 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<div class=\"basic_cart-grid basic-cart-block\">
  ";
        // line 2
        if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["basic_cart"] ?? null), "empty", [], "any", false, false, true, 2), "status", [], "any", false, false, true, 2)) {
            // line 3
            echo "    ";
            echo t("@basic_cart.empty.text", array("@basic_cart.empty.text" => twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source,             // line 4
($context["basic_cart"] ?? null), "empty", [], "any", false, false, true, 4), "text", [], "any", false, false, true, 4), ));
            // line 6
            echo "
  ";
        } else {
            // line 8
            echo "

    ";
            // line 10
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["basic_cart"] ?? null), "data", [], "any", false, false, true, 10), "contents", [], "any", false, false, true, 10));
            foreach ($context['_seq'] as $context["key"] => $context["value"]) {
                // line 11
                echo "      <div class=\"basic_cart-cart-contents row\">
        <div class=\"basic_cart-cart-node-title cell\">";
                // line 12
                echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["value"], "link", [], "any", false, false, true, 12), 12, $this->source), "html", null, true);
                echo " </div>
            ";
                // line 13
                if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["basic_cart"] ?? null), "config", [], "any", false, false, true, 13), "quantity_enabled", [], "any", false, false, true, 13)) {
                    // line 14
                    echo "              <div class=\"basic_cart-cart-quantity cell\">";
                    echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["value"], "quantity", [], "any", false, false, true, 14), 14, $this->source), "html", null, true);
                    echo "</div>
              <div class=\"basic_cart-cart-x cell\">x</div>
            ";
                }
                // line 16
                echo "  
        <div class=\"basic_cart-cart-unit-price cell\">
        <strong>";
                // line 18
                echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["value"], "price_value", [], "any", false, false, true, 18), 18, $this->source), "html", null, true);
                echo "</strong>    
        </div>
    </div>
    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['key'], $context['value'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 22
            echo "  
  <div class=\"basic_cart-cart-total-price-contents row\">
        <div class=\"basic_cart-total-price cell\">
            ";
            // line 25
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["basic_cart"] ?? null), "config", [], "any", false, false, true, 25), "total_price_label", [], "any", false, false, true, 25), 25, $this->source), "html", null, true);
            echo ": <strong> ";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["basic_cart"] ?? null), "config", [], "any", false, false, true, 25), "total_price", [], "any", false, false, true, 25), 25, $this->source), "html", null, true);
            echo " </strong>
        </div>
      </div> 


         ";
            // line 30
            if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["basic_cart"] ?? null), "config", [], "any", false, false, true, 30), "vat_enabled", [], "any", false, false, true, 30)) {
                // line 31
                echo "          <div class=\"basic_cart-block-total-vat-contents row\">
          <div class=\"basic_cart-total-vat cell\"> ";
                // line 32
                echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["basic_cart"] ?? null), "config", [], "any", false, false, true, 32), "vat_label", [], "any", false, false, true, 32), 32, $this->source), "html", null, true);
                echo " : <strong>";
                echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["basic_cart"] ?? null), "config", [], "any", false, false, true, 32), "total_price_vat", [], "any", false, false, true, 32), 32, $this->source), "html", null, true);
                echo "</strong></div>
        </div>
        ";
            }
            // line 35
            echo "


       <div class=\"basic_cart-cart-checkout-button basic_cart-cart-checkout-button-block row\">
        <a href='";
            // line 39
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["basic_cart"] ?? null), "config", [], "any", false, false, true, 39), "view_cart_url", [], "any", false, false, true, 39), 39, $this->source), "html", null, true);
            echo "' class='button'>";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["basic_cart"] ?? null), "config", [], "any", false, false, true, 39), "view_cart_button", [], "any", false, false, true, 39), 39, $this->source), "html", null, true);
            echo " </a>
      </div>


  ";
        }
        // line 44
        echo "</div>";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["basic_cart"]);    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "modules/basic_cart/templates/basic-cart-cart-template.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  134 => 44,  124 => 39,  118 => 35,  110 => 32,  107 => 31,  105 => 30,  95 => 25,  90 => 22,  80 => 18,  76 => 16,  69 => 14,  67 => 13,  63 => 12,  60 => 11,  56 => 10,  52 => 8,  48 => 6,  46 => 4,  44 => 3,  42 => 2,  39 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "modules/basic_cart/templates/basic-cart-cart-template.html.twig", "C:\\xampp\\htdocs\\MPS_EXPRESS\\drupal-10.2.3\\modules\\basic_cart\\templates\\basic-cart-cart-template.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("if" => 2, "trans" => 3, "for" => 10);
        static $filters = array("escape" => 4);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['if', 'trans', 'for'],
                ['escape'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
