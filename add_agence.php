<?php
$files = [
    'src/Entity/Maison.php',
    'src/Entity/Proprio.php',
    'src/Entity/ContratLocation.php',
    'src/Entity/Depenses.php',
    'src/Entity/Campagne.php',
    'src/Entity/FactureLocation.php',
    'src/Entity/Employe.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Check if property agence already exists
    if (strpos($content, 'private ?Agence $agence') !== false) {
        echo "$file already has agence.\n";
        continue;
    }

    $propertyCode = "
    #[ORM\ManyToOne(inversedBy: 'utilisateurs')] // reused same mappedBy vaguely or no inversedBy
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Agence \$agence = null;
";
    $propertyCode = str_replace("inversedBy: 'utilisateurs'", "targetEntity: Agence::class", $propertyCode);

    $getterSetterCode = "
    public function getAgence(): ?Agence
    {
        return \$this->agence;
    }

    public function setAgence(?Agence \$agence): static
    {
        \$this->agence = \$agence;

        return \$this;
    }
";

    // Insert property just before the first constructor or the first method
    if (preg_match('/(public function __construct|public function get(?:Id)?)/', $content, $matches, PREG_OFFSET_CAPTURE)) {
        $insertPos = $matches[0][1];
        $content = substr_replace($content, $propertyCode . "\n    ", $insertPos, 0);
    } else {
        // Fallback to end of class before last closing brace
        $insertPos = strrpos($content, '}');
        $content = substr_replace($content, "\n" . $propertyCode . "\n", $insertPos, 0);
    }

    // Insert getters and setters at the end of the class
    $insertPos = strrpos($content, '}');
    $content = substr_replace($content, "\n" . $getterSetterCode . "\n", $insertPos, 0);

    file_put_contents($file, $content);
    echo "Updated $file\n";
}
