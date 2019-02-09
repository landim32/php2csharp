using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;

namespace PHP2CSharp.Converters
{
    public abstract class BaseConverter
    {
        private const string FUNCTION_CONTENT = @"[public|protected|private]+\s*[static|]+\s*function\s*[0-9,a-z,A-Z,_]+\(.*?\)";

        public abstract string convert(string sourceCode);

        protected string getTrueType(string typeName)
        {
            if (typeName.StartsWith("null|", true, null))
            {
                typeName = typeName.Substring(5);
            }
            else if (typeName.EndsWith("|null", true, null))
            {
                typeName = typeName.Substring(0, typeName.Length - 5);
            }
            else if (typeName.EndsWith("[]", true, null))
            {
                typeName = "IList<" + typeName.Substring(0, typeName.Length - 2) + ">";
            }
            return typeName;
        }

        protected string convertFunctions(string sourceCode, Func<string, string> convertFunction)
        {
            var currentSource = sourceCode;
            var sb = new StringBuilder();
            var funcContentList = Regex.Split(currentSource, FUNCTION_CONTENT);
            foreach (var funcContent in funcContentList)
            {
                int index = currentSource.IndexOf(funcContent);
                if (index >= 0)
                {
                    var funcContentConv = convertFunction(funcContent);
                    sb.Append(currentSource.Substring(0, index));
                    sb.Append(funcContentConv);
                    //sb.Append(currentSource.Substring(index + funcContent.Length));
                    currentSource = currentSource.Substring(index + funcContent.Length);
                }
                else {
                    throw new Exception("Function content not found!");
                }
            }
            sb.Append(currentSource);
            return sb.ToString();
        }
    }
}
