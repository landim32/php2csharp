using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;

namespace PHP2CSharp.Converters
{
    public class ConstantConverter : BaseConverter
    {
        private const string CONSTANT_STRING = @"const\s*([0-9,a-z,A-Z,_]+)\s*=\s*\""(.*?)\""\s*;";
        private const string CONSTANT_INT = @"const\s*([0-9,a-z,A-Z,_]+)\s*=\s*([0-9]+)\s*;";
        private const string CONSTANT_VAR = "([0-9,a-z,A-Z,_]+)::([0-9,a-z,A-Z,_]+)";

        public override string convert(string sourceCode)
        {
            sourceCode = Regex.Replace(sourceCode, CONSTANT_STRING, delegate (Match match) {
                return "public const string " + match.Groups[1].Value + " = \"" + match.Groups[2].Value + "\";";
            }, RegexOptions.IgnoreCase);
            sourceCode = Regex.Replace(sourceCode, CONSTANT_INT, delegate (Match match) {
                return "public const int " + match.Groups[1].Value + " = " + match.Groups[2].Value + ";";
            }, RegexOptions.IgnoreCase);
            sourceCode = Regex.Replace(sourceCode, CONSTANT_VAR, delegate (Match match) {
                return match.Groups[1].Value + "." + match.Groups[2].Value;
            }, RegexOptions.IgnoreCase);
            return sourceCode;
        }
    }
}
