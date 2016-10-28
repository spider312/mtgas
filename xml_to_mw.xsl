<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
 <xsl:output method="text" indent='no' />
 <xsl:template match="/cockatrice_deck">
  <xsl:text>// NAME: </xsl:text><xsl:value-of select="deckname"/><xsl:text>&#xa;</xsl:text>
  <xsl:for-each select="zone/card">
   <xsl:if test="../@name = 'side'"><xsl:text>SB: </xsl:text></xsl:if>
   <xsl:value-of select="@number"/>
   <xsl:text> </xsl:text>
   <xsl:value-of select="@name"/>
   <xsl:text>&#xa;</xsl:text>
  </xsl:for-each>
 </xsl:template>
</xsl:stylesheet>
