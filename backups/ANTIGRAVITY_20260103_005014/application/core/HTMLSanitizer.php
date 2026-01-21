<?php
// core/HTMLSanitizer.php
// Sanitization HTML avancée (Alternative à HTMLPurifier sans dépendance)

class HTMLSanitizer {
    
    /**
     * Liste des balises autorisées
     */
    private static $allowedTags = [
        'p', 'br', 'a', 'strong', 'em', 'b', 'i', 'u',
        'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'table', 'thead', 'tbody', 'tr', 'td', 'th',
        'blockquote', 'code', 'pre', 'span', 'div'
    ];
    
    /**
     * Attributs autorisés par balise
     */
    private static $allowedAttributes = [
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'table' => ['class'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
        '*' => ['class', 'id', 'style'] // Attributs globaux
    ];
    
    /**
     * Nettoie le HTML en supprimant les éléments dangereux
     * @param string $html HTML brut
     * @param bool $allowImages Autoriser les images
     * @return string HTML nettoyé
     */
    public static function clean($html, $allowImages = false) {
        if (empty($html)) {
            return '';
        }
        
        // Ajouter img aux balises autorisées si demandé
        $tags = self::$allowedTags;
        if ($allowImages) {
            $tags[] = 'img';
        }
        
        // Étape 1 : Supprimer les scripts et styles inline dangereux
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $html = preg_replace('/on\w+\s*=\s*["\'].*?["\']/i', '', $html); // onclick, onload, etc.
        
        // Étape 2 : Utiliser strip_tags avec balises autorisées
        $allowedTagsStr = '<' . implode('><', $tags) . '>';
        $html = strip_tags($html, $allowedTagsStr);
        
        // Étape 3 : Nettoyer les attributs
        $html = self::cleanAttributes($html);
        
        // Étape 4 : Bloquer les URLs javascript:
        $html = preg_replace('/href\s*=\s*["\']javascript:/i', 'href="#blocked-', $html);
        
        return $html;
    }
    
    /**
     * Nettoie les attributs HTML
     */
    private static function cleanAttributes($html) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//*[@*]');
        
        foreach ($nodes as $node) {
            $tagName = strtolower($node->nodeName);
            $allowedAttrs = self::$allowedAttributes[$tagName] ?? [];
            $allowedAttrs = array_merge($allowedAttrs, self::$allowedAttributes['*'] ?? []);
            
            // Supprimer les attributs non autorisés
            $attributesToRemove = [];
            foreach ($node->attributes as $attr) {
                if (!in_array(strtolower($attr->name), $allowedAttrs)) {
                    $attributesToRemove[] = $attr->name;
                }
            }
            
            foreach ($attributesToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
            
            // Nettoyer l'attribut style (supprimer expression, behavior, etc.)
            if ($node->hasAttribute('style')) {
                $style = $node->getAttribute('style');
                $style = preg_replace('/(expression|behavior|javascript|vbscript)/i', '', $style);
                $node->setAttribute('style', $style);
            }
        }
        
        return $dom->saveHTML();
    }
    
    /**
     * Convertit du texte brut en HTML sécurisé
     */
    public static function textToHtml($text) {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $text = nl2br($text);
        return $text;
    }
    
    /**
     * Extrait le texte brut d'un HTML
     */
    public static function toPlainText($html) {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        return trim($text);
    }
}
